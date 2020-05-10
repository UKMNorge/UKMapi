<?php

namespace UKMNorge\Slack\API\Response\Plugin\Transport;

class BlockAction extends Transport implements TransportInterface {
    public $value;
    
    public function _post_construct() {
        $this->setId( $this->getData()->trigger_id );
    }

    public function setValue(String &$value) {
        $this->value = $value;
        return $this;
    }

    public function getValue() {
        return $this->value;
    }
}