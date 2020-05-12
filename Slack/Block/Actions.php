<?php

namespace UKMNorge\Slack\Block;

use stdClass;
use UKMNorge\Slack\Block\Structure\Block;
use UKMNorge\Slack\Block\Structure\Collection\Elements;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

/**
 * Actions block
 * 
 * @see https://api.slack.com/reference/block-kit/blocks#actions
 */
class Actions extends Block {
    const TYPE = 'actions';
    public $elements;

    /**
     * Get collection of elements
     *
     * @return Elements
     */
    public function getElements() {
        if( is_null( $this->elements)) {
            $this->elements = new Elements(5);
        }
        return $this->elements;
    }

    /**
     * Export data
     *
     * @return stdClass
     */
    public function export() {
        $data = parent::export();
        
        if( $this->getElements()->getLength() == 0 ) {
            throw new Exception('Actions blocks must contain at least one element', 'missing_elements');
        }
        $data->elements = Payload::convert($this->getElements());
        return $data;
    }
}