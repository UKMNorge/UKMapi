<?php

namespace UKMNorge\Slack\API;

use UKMNorge\Slack\Exceptions\SetupException;
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

    /**
     * Update a slack message
     *
     * @param Message $message
     * @return String slack-response
     */
    public static function update( Message $message ) {
        if( is_null($message->getTimestamp())) {
            throw new SetupException('Updating messages requires original message timestamp (see Payload\Message::setTimestamp())');
        }
        return App::botPost('chat.update', (array)$message->export());
    }
}