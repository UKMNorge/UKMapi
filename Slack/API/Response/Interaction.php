<?php

namespace UKMNorge\Slack\API\Response;

use stdClass;
use UKMNorge\Slack\API\App;
use UKMNorge\Slack\BlockAction;
use UKMNorge\Slack\Log;
use UKMNorge\Slack\Exceptions\VerificationException;
use UKMNorge\Slack\API\Response\Plugin\Filter\FilterInterface;
use UKMNorge\Slack\API\Response\Plugin\Filter\BlockAction as BlockActionFilter;
use UKMNorge\Slack\API\Response\Plugin\Filter\Trigger as TriggerFilter;
use UKMNorge\Slack\API\Response\Plugin\Filter\ViewSubmission as ViewSubmissionFilter;
use UKMNorge\Slack\API\Response\Plugin\Transport\TransportInterface;
use UKMNorge\Slack\API\Response\Plugin\Transport\BlockAction as BlockActionTransport;
use UKMNorge\Slack\API\Response\Plugin\Transport\Trigger as TriggerTransport;
use UKMNorge\Slack\API\Response\Plugin\Transport\ViewSubmission as ViewSubmissionTransport;

class Interaction extends Log {

    private $has_async = false;
    private $filters = [];
    private $response;

    private static $syncMode = 'SYNC';

    /**
     * Register a new filter
     *
     * @param String Slack callback id
     * @param Filter Filter object
    */
    public function addFilter(FilterInterface $filter ) {
        // Set up arrays
        if( !isset( $this->filters[$filter::TYPE] ) ) {
            $this->filters[$filter::TYPE] = [
                'ASYNC' => [],
                'SYNC'=> []
            ];
        }
        
        $this->filters[$filter::TYPE][$filter::ASYNC ? 'ASYNC' : 'SYNC'][ $filter->getPriority() ] = $filter;
    }

    /**
     * Initiate a new interaction
     *
     * @param String request_body
     * @return void
    */
    public function __construct( String $request_body ) {
        $this->log('Create Interaction');
        $this->verify($request_body);
        
        $this->request = $request_body;
        $this->data = json_decode($_POST['payload']);
        $this->response = new stdClass();
        $this->response->status = 'ok'; // default to all okay (positive thinkin')

        $this->log('- payload: '. var_export($this->data,true));
    }

    /**
     * Verify source of interaction
     *
     * @return Bool
     * @throws VerificationException
    */
    private function verify( $request_body ) {
        App::verifyRequestOrigin( $request_body );
        $this->log('- verified');
        return true;
    }

    /**
     * Process the interaction
     *
     */
    public function process() {
        $this->log('- registered filters: '. var_export( $this->filters, true));
        $this->log('- process '. static::$syncMode);
        if( isset($this->data->callback_id ) ) {
            $this->processTrigger();
        }
        elseif( isset($this->data->type)) {
            switch($this->data->type) {
                // VIEW ACTION
                case 'block_actions':
                    $this->processBlockAction();
                break;
                case 'view_submission':
                    $this->processViewSubmission();
                break;
                default:
                    $this->log('Unknown data type');
                break;
            }
        }
        else {
            $this->log('UNKNOWN PROCESS ALGORITHM');
        }
    }

    /**
     * Process interaction of type trigger
     *
    */
    private function processTrigger() {
        $this->log('- process trigger');
    
        $transport = new TriggerTransport( $this->data, $this->response );
        $this->applyFilters( TriggerFilter::TYPE , $transport );
    }

    /**
     * Process the data of a block action
    */
    private function processBlockAction() {
        switch($this->data->container->type) {
            // Data from a view
            case 'static_select':
                $this->log('--> from static_select (do:view)');
            case 'view':
                $this->log('--> from view');
                // Fetch value from actions
                foreach( $this->data->actions as $action ) {
                    $value = BlockAction::getValueFromField( $action );
                    $this->log('--> action: '. $action->action_id . '('. $action->type .') @ '. var_export($value,true));                 
                    
                    if( !is_null( $value ) ) {
                        $action = BlockAction::create( $this->data->container->view_id, $action->action_id, $value);
                    }
                    
                    $transport = new BlockActionTransport( $this->data, $this->response );
                    $transport->setId(strval($action->action_id));
                    $transport->setValue( $value );
                    $this->applyFilters(BlockActionFilter::TYPE, $transport );
                }
            break;
            case 'message':
                $this->log('--> from message');
                foreach( $this->data->actions as $action ) {
                    $transport = new BlockActionTransport( $this->data, $this->response );
                    $transport->setId(strval($action->action_id));
                    $this->applyFilters(BlockActionFilter::TYPE, $transport );
                }
            break;
            default:
                $this->log('--> unknown container type '. $this->data->container->type);
        }
    }

    public function processViewSubmission() {
        $this->log('- process view submission');
        foreach( $this->data->view->state->values as $value_id => $value_array ) {
            foreach( $value_array as $field_id => $field ) {
                $value = BlockAction::getValueFromField( $field );
                if( !is_null( $value ) ) {
                    $action = BlockAction::create( 
                        $this->data->view->id, 
                        $field_id,
                        $value
                    );
                } else {
                    $this->log('-> empty block action value!');
                }
            }
        }

        $transport = new ViewSubmissionTransport( $this->data, $this->response );
        $this->applyFilters( ViewSubmissionFilter::TYPE , $transport );
    }

    public function output() {
        $this->log('-> output');
        header("Content-type: application/json; charset=utf-8");
        header('HTTP/1.0 200 OK', true, 200);

        if( is_null($this->response) ) {
            return true;
        }
        
        if( is_object($this->response) && method_exists( $this->response, 'renderToJson' )) {
            $output = $this->response->renderToJson();
        } else {
            $output = json_encode($this->response);
        }
        $this->log('--> response: '.$output);
        echo $output;
        error_log('--> flush()');
        flush();
    }

    private function applyFilters( String $filter_type, TransportInterface $transport ) {
        $this->log('- applyFilters('.$filter_type.'::'. static::$syncMode .')');
        // Iterate through all synchronous filters and process them
        if( isset($this->filters[ $filter_type ][static::$syncMode])) {
            foreach( $this->filters[ $filter_type ][static::$syncMode] as $syncFilter ) {
                $this->log('-- filter: '. get_class($syncFilter));
                // Skip if filter is irrelevant
                if( !$syncFilter->condition( $transport ) ) {
                    $this->log('--- failed condition');
                    continue;
                }
                $this->log('--- processing');
                $transport = $syncFilter->process( $transport);
                $this->log('- current transport: '. var_export($transport, true));
            }
            $this->response = $transport->getResponse();
        } else {
            $this->log('-- no filters found');
        }

        // If there are any asynchronous filters, add the transaction to a async process queue
        if( static::$syncMode != 'ASYNC' && is_array( $this->filters[ $filter_type ]['ASYNC']) && sizeof( $this->filters[ $filter_type ]['ASYNC'] ) > 0 ) {
            $this->setHasAsync();
        }
    }

    private function setHasAsync() {
        $this->has_async = true;
    }

    public function hasUnprocessedAsyncFilters() {
        return $this->has_async;
    }

    public function processAsync() {
        static::$syncMode = 'ASYNC';
        $this->process();
    }
}