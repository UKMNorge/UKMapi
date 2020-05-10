<?php

namespace UKMNorge\Slack\Block\Structure;

class Exception extends \Exception {

    public $id;

    public function __construct( String $message, String $id, Int $code = null )
    {
        parent::__construct( $message, $code );
        $this->id = $id;
    }


    public function getId() {
        return $this->id;
    }
}