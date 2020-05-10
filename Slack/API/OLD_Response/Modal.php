<?php

namespace UKMNorge\Slack\API\Response;

class Modal {
    public $trigger_id;
    public $template_id;
    public $view;
    public $data;
    public $view_id;
    public $endpoint = 'views.open';
    public $response_action;

    /**
     * Create modal with stored template
     *
     * @return Modal
    */
    public function __construct( String $trigger_id, String $template_id ) {
        if( empty($trigger_id)) {
            $trigger_id = null;
        }
        $this->trigger_id = $trigger_id;
        $this->template_id = $template_id;
    }

    /**
     * Render data array for Slack
     *
     * @return Array $data
    */
    public function render() {
        $this->template = Template::getByName($this->getTemplateId());
        $this->view = $this->template->render( $this->getData() );

        $data = [
            'view' => (array) $this->view
        ];

        $test = [
            'trigger_id' => 'TriggerId',
            'response_action' => 'ResponseAction',
            'view_id' => 'ViewId'
        ];
        foreach( $test as $key => $function_name ) {
            $function = 'get'.$function_name;
            if( !is_null( $this->$function() ) ) {
               $data[$key] = $this->$function();
            }
        }

        return $data;
    }

    /**
     * Render data array as JSON for Slack
     *
     * @return String json-encoded data
    */
    public function renderToJson() {
        return json_encode( $this->render() );
    }

    /**
     * Get the Template ID
     *
     * @return String
    */
    public function getTemplateId() {
        return $this->template_id;
    }

    /**
     * Get the trigger ID
     *
     * @return String
    */
    public function getTriggerId() {
        return $this->trigger_id;
    }

    /**
     * Add (render) data
     *
     * @param String data key
     * @param mixed value
     * @return self
    */
    public function addData( String $key, $value ) {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Get all data (for render)
     *
     * @return Array data
    */
    public function getData() {
        return $this->data;
    }

    /**
     * Set view id
     *
     * @param String
     * @return self
     */
    public function setViewId( String $view_id ) {
        $this->view_id = $view_id;
        return $this;
    }

    /**
     * Get the view id
     *
     * @return String
     */
    public function getViewId() {
        return $this->view_id;
    }

    /**
     * Update the modal API endpoint
     *
     * @default views.open
     * @param String endpoint
     * @return self
     */
    public function setEndpoint(String $endpoint ) {
        $this->endpoint = $endpoint;
        return $this;
    }

    /**
     * Get the api endpoint
     * 
     * @return String
     */
    public function getEndpoint() {
        return $this->endpoint;
    }

    public function setResponseAction( String $action ) {
        $this->response_action = $action;
        return $this;
    }

    public function getResponseAction() {
        return $this->response_action;
    }
}