<?php

namespace UKMNorge\Slack\Block;

use stdClass;
use UKMNorge\Slack\Block\Structure\Block;
use UKMNorge\Slack\Payload\Payload;
use UKMNorge\Slack\Block\Structure\Exception;

/**
 * File block
 * 
 * @see https://api.slack.com/reference/block-kit/blocks#file
 */
class File extends Block {
    const TYPE = 'file';
    public $source = 'remote';
    public $external_id;

    public function __construct( String $external_id )
    {
        $this->setExternalId($external_id);
    }

    /**
     * Set file external id
     *
     * @param String $alt_text
     * @return self
     */
    public function setExternalId( String $external_id ) {
        $this->external_id = $external_id;
        return $this;
    }

    /**
     * Get file external id
     *
     * @return String
     */
    public function getExternalId() {
        return $this->external_id;
    }

    /**
     * Get the file source
     *
     * @return String remote (always)
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * Export object
     *
     * @return stdClass
     */
    public function export() {
        $data = parent::export();
        $data->source = Payload::convert($this->getSource());

        // External id
        if( is_null( $this->getExternalId() ) ) {
            throw new Exception('Missing required external id of file', 'missing_external_id');
        }
        $data->external_id = Payload::convert($this->getExternalId());

        return $data;
    }
}