<?php

namespace UKMNorge\Slack\Payload;

use stdClass;
use UKMNorge\Slack\Block\Composition\Text;

class Message extends Payload {
    const TYPE = 'message';

    public $timestamp;

    public function __construct( String $channel_id, Text $text ) {
        $this->channel_id = $channel_id;
        $this->text = $text;
    }

    /**
     * Set original message timestamp
     *
     * @param String $timestamp
     * @return self
     */
    public function setTimestamp(String $timestamp ) {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * Fetch original message timestamp
     * 
     * Used when updating existing message
     *
     * @return String
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Get the exportdata
     *
     * @return stdClass
     */
    public function export() {
        $data = parent::export();

        $data->channel = $this->channel_id;
        $data->text = $this->text->getText();

        if( !is_null($this->timestamp)) {
            $data->timestamp = $this->getTimestamp();
        }

        return $data;
    }

}