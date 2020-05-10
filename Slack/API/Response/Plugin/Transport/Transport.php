<?php

namespace UKMNorge\Slack\API\Response\Plugin\Transport;

use stdClass;

class Transport implements TransportInterface {
    private $response;
    private $data;
    public $id;

    final public function __construct( stdClass $interaction_data, stdClass $interaction_response ) {
        $this->response = $interaction_response;
        $this->data = $interaction_data;

        if( method_exists( get_called_class(), '_post_construct' ) ) {
            static::_post_construct();
        }
    }

    final public function getResponse() {
        return $this->response;
    }

    final public function setResponse( $response ) {
        $this->response = $response;
    }

    final public function getData() {
        return $this->data;
    }

    final public function getId() {
        return $this->id;
    }

    public function setId( String $id ) {
        $this->id = $id;
        return $this;
    }
}
