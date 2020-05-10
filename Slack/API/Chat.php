<?php

namespace UKMNorge\Slack\API;

use UKMNorge\Slack\Payload\Message;

class Chat {

    /**
     * Post a message to Slack
     *
     * @param Message $message
     * @return String slack-response
     */
    public static function post( Message $message ) {
        return App::botPost('chat.postMessage', (array)$message->export() );
    }
}