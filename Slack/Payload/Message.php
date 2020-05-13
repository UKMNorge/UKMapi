<?php

namespace UKMNorge\Slack\Payload;

use stdClass;
use UKMNorge\Slack\Block\Composition\Text;

class Message extends Payload
{
    const TYPE = 'message';

    public $timestamp;
    public $as_user;

    public function __construct(String $channel_id, Text $text)
    {
        $this->channel_id = $channel_id;
        $this->text = $text;
    }

    /**
     * Set whether to post as authenticated user (bot or real)
     *
     * @param Bool $status
     * @return self
     */
    public static function setAsUser(Bool $status)
    {
        $this->as_user = $status;
        return $this;
    }

    /**
     * Get whether to post as authenticated user (bot or real)
     *
     * @return Bool
     */
    public function getAsUser()
    {
        return $this->as_user;
    }

    /**
     * Set original message timestamp
     *
     * @param String $timestamp
     * @return self
     */
    public function setTimestamp(String $timestamp)
    {
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
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Get the exportdata
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export();

        $data->channel = $this->channel_id;
        $data->text = $this->text->getText();

        if (!is_null($this->timestamp)) {
            $data->timestamp = $this->getTimestamp();
        }

        if (!is_null($this->as_user)) {
            $data->timestamp = $this->getAsUser() ? 'true' : 'false';
        }

        return $data;
    }
}
