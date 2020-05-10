<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Composition\Option;
use UKMNorge\Slack\Block\Structure\Collection\Options;
use UKMNorge\Slack\Block\Structure\Element;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

class Checkboxes extends Element {
    const TYPE = 'checkboxes';
    const REQUIRE_ACTION_ID = true;

    public $options;
    public $initial_options;

    /**
     * Create a group of checkboxes
     *
     * @param String $action_id
     * @param Array<Option> $options
     */
    public function __construct( String $action_id, Array $options )
    {
        $this->setActionId($action_id);
        $this->getOptions()->set($options);
    }

    /**
     * Get collection of checkbox options
     *
     * @return Options
     */
    public function getOptions() {
        if( is_null($this->options)) {
            $this->options = new Options(0);
        }
        return $this->options;
    }

    /**
     * Get collection of checkbox initial options
     *
     * @return Options
     */
    public function getInitialOptions() {
        if( is_null($this->initial_options)) {
            $this->initial_options = new Options(0);
        }
        return $this->initial_options;
    }

    /**
     * Get export data
     *
     * @return stdClass
     */
    public function export() {
        $data = parent::export(); // type + action_id (if not null)

        // Options
        if( $this->getOptions()->getLength() == 0 ) {
            throw new Exception('Checkboxes require at least one option', 'missing_options');
        }
        $data->options = Payload::convert($this->getOptions());

        // Initial options
        if( $this->getInitialOptions()->getLength() > 0 ) {
            $data->initial_options = Payload::convert($this->getInitialOptions());
        }

        return $data;
    }
}