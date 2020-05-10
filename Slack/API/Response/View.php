<?php

namespace UKMNorge\Slack\API\Response;

use UKMNorge\Slack\BlockAction;

class View {

    public $id;
    public $callback_id;

    public function __construct( String $id, String $callback_id ) {
        #error_log(' -> Response\View: '. $id .' => '. $callback_id);
        $this->id = $id;
        $this->callback_id = $callback_id;
    }

    public function collectSubmittedData() {
        return BlockAction::getAllFromView( $this->getId() );
    }

    public function getId() {
        return $this->id;
    }

    public function getCallbackId() {
        return $this->callback_id;
    }
}