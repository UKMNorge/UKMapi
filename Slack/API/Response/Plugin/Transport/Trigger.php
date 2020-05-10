<?php

namespace UKMNorge\Slack\API\Response\Plugin\Transport;

class Trigger extends Transport implements TransportInterface {
    public function _post_construct() {
        $this->setId( $this->getData()->trigger_id );
    }
}
