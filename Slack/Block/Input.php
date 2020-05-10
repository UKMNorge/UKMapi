<?php

namespace UKMNorge\Slack\Block;

use stdClass;
use UKMNorge\Slack\Block\Composition\PlainText;
use UKMNorge\Slack\Block\Element\Datepicker;
use UKMNorge\Slack\Block\Element\ElementInterface;
use UKMNorge\Slack\Block\Element\Input as InputElement;
use UKMNorge\Slack\Block\Element\MultiSelect;
use UKMNorge\Slack\Block\Element\Select;
use UKMNorge\Slack\Block\Structure\Block;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

/**
 * Input block
 * 
 * @see https://api.slack.com/reference/block-kit/blocks#input
 */
class Input extends Block
{
    const TYPE = 'input';
    public $label;
    public $element;
    public $hint;
    public $optional;

    /**
     * Create new input block
     *
     * @param Composition\Text $label
     * @param ElementInterface $element
     */
    public function __construct( PlainText $label, ElementInterface $element ) {
        $this->setLabel($label);
        $this->setElement($element);        
    }

    /**
     * Set input label
     *
     * @param PlainText $text
     * @return self
     */
    public function setLabel(PlainText $text)
    {
        if ($text->getLength() > 2000) {
            throw new Exception('Label length cannot be more than 2000 characters', 'maxlength_label');
        }
        $this->label = $text;
        return $this;
    }

    /**
     * Get label
     *
     * @return Composition\Text
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set input element
     *
     * @param ElementInterface $element
     * @return self
     */
    public function setElement(ElementInterface $element)
    {
        if (!in_array(
            get_class($element),
            [
                InputElement::class,
                Select::class,
                MultiSelect::class,
                Datepicker::class
            ]
        )) {
            throw new Exception('Invalid element type for input', 'invalid_element_type');
        }
        $this->element = $element;
        return $this;
    }

    /**
     * Get input element
     *
     * @return ElementInterface
     */
    public function getElement()
    {
        return $this->element;
    }

    /**
     * Set hint
     *      *
     * @param PlainText $text
     * @return self
     */
    public function setHint(PlainText $text)
    {
        if( $text->getLength() > 2000 ) {
            throw new Exception('Hint must be less than 2000 characters. '. $text->getLength() .' given.', 'maxlength_hint');
        }
        $this->hint = $text;
        return $this;
    }

    /**
     * Get hint
     *
     * @return PlainText
     */
    public function getHint()
    {
        return $this->hint;
    }

    public function setMultiline( Bool $status ) {
        $this->getElement()->setMultiline($status);
    }

    /**
     * Set whether the input should be optional
     * 
     * Default: null (meaning false)
     *
     * @param Bool $status
     * @return self
     */
    public function setOptional(Bool $status)
    {
        $this->optional = $status;
        return $this;
    }

    /**
     * Should this input be optional?
     *
     * Default: null (meaning false)
     * 
     * @return Bool|null
     */
    public function getOptional()
    {
        return $this->optional;
    }

    /**
     * Export data
     *
     * @return ExportData
     */
    public function export()
    {
        $data = parent::export();

        // Label
        if( is_null($this->getLabel()) ) {
            throw new Exception('Input label is required','missing_input_label');
        }
        $data->label = Payload::convert($this->getLabel());

        // Element
        if( is_null($this->getElement())) {
            throw new Exception('Input requires an element', 'missing_element');
        }
        $data->element = Payload::convert($this->getElement());

        // Hint
        if( !is_null($this->getHint())) {
            $data->hint = Payload::convert($this->getHint());
        }

        // Optional
        if( !is_null($this->getOptional())) {
            $data->optional = Payload::convert($this->getOptional());
        }
        return $data;
    }
}
