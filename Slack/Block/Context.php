<?php

namespace UKMNorge\Slack\Block;

use stdClass;
use UKMNorge\Slack\Block\Composition\Text;
use UKMNorge\Slack\Block\Composition\PlainText;
use UKMNorge\Slack\Block\Composition\Markdown;
use UKMNorge\Slack\Block\Element\Image;
use UKMNorge\Slack\Block\Structure\Block;
use UKMNorge\Slack\Block\Structure\Collection\Elements;
use UKMNorge\Slack\Payload\Payload;

/**
 * Context block
 * 
 * @see https://api.slack.com/reference/block-kit/blocks#context
 */
class Context extends Block {
    const TYPE = 'context';
    public $elements;

    /**
     * Create a new context block
     *
     * @param Array<ElementInterface> $elements
     */
    public function __construct( Array $elements = null){
        if(!is_null($elements)) {
            $this->getElements()->set($elements);
        }
    }

    /**
     * Get collection of elements
     *
     * @return Elements
     */
    public function getElements() {
        if( is_null($this->elements ) ) {
            $this->elements = new Elements(10);
            $this->elements->setSupportedClasses(
                [
                    Text::class,
                    PlainText::class,
                    Markdown::class,
                    Image::class
                ]
            );
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
            throw new Exception('Context blocks must contain at least one element', 'missing_elements');
        }
        $data->elements = Payload::convert($this->getElements());
        return $data;
    }
}