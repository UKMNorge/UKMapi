<?php

namespace UKMNorge\Slack\Block\Composition;

use stdClass;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

/**
 * Filter composition
 * 
 * @see https://api.slack.com/reference/block-kit/composition-objects#filter_conversations
 */
class Filter {
    public $include;
    public $exclude_external;
    public $exclude_bots;

    const ALLOWED_TYPES = ['im','mpim','private','public'];

    /**
     * Create a new Filter for conversations selection
     */
    public function __construct()
    {        
    }

    /**
     * Set types of conversations to include
     *
     * @param Array $types
     * @return self
     */
    public function setInclude( Array $types ) {
        $diff = array_diff( $types, static::ALLOWED_TYPES );

        if( sizeof( $diff ) > 0 ) {
            throw new Exception(
                'Unsupported filter include types: '. join(', ',$diff),
                'invalid_types'
            );
        }
        $this->include = join(',', $types);
        return $this;
    }

    /**
     * Get types of conversations to include
     *
     * @return String
     */
    public function getInclude() {
        return $this->include;
    }

    /**
     * Set whether external and shared channels should be excluded
     *
     * @param Bool $status
     * @return void
     */
    public function setExcludeExternalChannels( Bool $status ) {
        $this->exclude_external = $status;
        return $this;
    }
    /**
     * Should external and shared channels be excluded?
     * 
     * @return Bool
     */
    public function getExcludeExternalChannels() {
        return $this->exclude_external;
    }

    /**
     * Set whether bots should be excluded
     *
     * @param Bool $status
     * @return self
     */
    public function setExcludeBots( Bool $status ) {
        $this->exclude_bots = $status;
        return $this;
    }

    /**
     * Should bots be excluded?
     *
     * @return Bool
     */
    public function getExcludeBots() {
        return $this->exclude_bots;
    }


    /**
     * Export data
     *
     * @return stdClass
     */
    public function export() {
        $data = new stdClass();
        
        if( !is_null($this->getInclude())) {
            $data->include = Payload::convert($this->getInclude());
        }

        if( !is_null($this->getExcludeExternalChannels()())) {
            $data->exclude_external_shared_channels = Payload::convert( $this->getExcludeExternalChannels());
        }

        if( !is_null($this->getExcludeBots())) {
            $data->exclude_bot_users = Payload::convert( $this->getExcludeBots());
        }

        return $data;
    }
}