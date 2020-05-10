<?php

namespace UKMNorge\Slack\Payload;

use stdClass;
use UKMNorge\Slack\Block\Composition\Text;

class Message extends Payload {
    const TYPE = 'message';

    public function __construct( String $channel_id, Text $text ) {
        $this->channel_id = $channel_id;
        $this->text = $text;
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

        return $data;
    }

}