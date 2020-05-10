<?php

namespace UKMNorge\Slack\Block;

use stdClass;
use UKMNorge\Slack\Block\Element\ElementInterface;
use UKMNorge\Slack\Block\Structure\Block;
use UKMNorge\Slack\Block\Structure\Collection\Fields;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

/**
 * Section block
 * 
 * @see https://api.slack.com/reference/block-kit/blocks#section
 */
class Section extends Block {
    const TYPE = 'section';
    public $text;
    public $fields;
    public $accessory;

    /**
     * Create a new section
     *
     * @param Composition\Text $text
     * @return self
     */
    public function __construct( Composition\Text $text = null)
    {
        if( !is_null($text)) {
            $this->setText($text);
        }
    }

    /**
     * Set text
     *
     * @param Composition\Text $text
     * @return self
     */
    public function setText( Composition\Text $text ) {
        if( $text->getLength() > 3000 ) {
            throw new Exception('Max text length is 3000 for sections', 'maxlength_text');
        }
        $this->text = $text;
        return $this;
    }
    
    /**
     * Get text
     *
     * @return Composition\Text $text
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Get fields collection
     *
     * @return Fields
     */
    public function getFields() {
        if( is_null( $this->fields ) ) {
            $this->fields = new Fields(10);
        }
        return $this->fields;
    }

    /**
     * Get the accessory
     *
     * @return Element\ElementInterface
     */
    public function getAccessory() {
        return $this->accessory;
    }

    /**
     * Set the accessory
     *
     * @param ElementInterface $accessory
     * @return self
     */
    public function setAccessory( ElementInterface $accessory ) {
        $this->accessory = $accessory;
        return $this;
    }

    /**
     * Export data
     *
     * @return stdClass
     */
    public function export() {
        $data = parent::export();
        
        if( is_null($this->getText() ) && $this->getFields()->getLength() == 0 ) {
            throw new Exception('Section requires either text or fields to be set', 'missing_field_and_text');
        }
        if( !is_null($this->getText())) {
            $data->text = Payload::convert($this->getText());
        }
        if( $this->getFields()->getLength() > 0 ) {
            $data->fields = Payload::convert($this->getFields());
        }
        if(!is_null($this->getAccessory())) {
            $data->accessory = Payload::convert($this->getAccessory());
        }
        return $data;
    }
}