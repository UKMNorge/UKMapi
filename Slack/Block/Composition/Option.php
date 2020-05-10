<?php


namespace UKMNorge\Slack\Block\Composition;

use stdClass;
use UKMNorge\Slack\Block\Element\Checkboxes;
use UKMNorge\Slack\Block\Element\Overflow;
use UKMNorge\Slack\Block\Element\Radio;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

/**
 * Option composition
 * 
 * @see https://api.slack.com/reference/block-kit/composition-objects#option
 */
class Option
{

    const MAX_VALUE_LENGTH = 75;
    const MAX_TEXT_LENGTH = 75;
    const MAX_DESCRIPTION_LENGTH = 75;
    const MAX_URL_LENGTH = 3000;

    public $text;
    public $value;
    public $description;
    public $url;
    public $context;

    /**
     * Create new option
     *
     * @param String Classname of container object (i.e Radio::class)
     * @param Text text
     * @param String $value
     */
    public function __construct(String $context, Text $text, String $value)
    {
        $this->context = $context;
        $this->setText($text);
        $this->setValue($value);
    }

    /**
     * Get current context
     *
     * @return String
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set the text
     * 
     * NOTE: markdown is only supported for radio buttons and checkboxes!
     *
     * @param Text $text
     * @return self
     */
    public function setText(Text $text)
    {
        if ($text->getLength() > static::MAX_TEXT_LENGTH) {
            throw new Exception(
                'Text must be shorter than ' . static::MAX_TEXT_LENGTH . ' characters. ' . $text->getLength() . ' given.',
                'maxlength_text'
            );
        }

        if ($text->isMarkdown() && !$this->inContext([Radio::class, Checkboxes::class])) {
            throw new Exception(
                'Markdown text is only supported for radio buttons and checkboxes',
                'invalid_text_type'
            );
        }
        $this->text = $text;
        return $this;
    }

    /**
     * Get the text
     *
     * @return Text
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set the value
     *
     * @param String $value
     * @return self
     */
    public function setValue(String $value)
    {
        if (strlen($value) > static::MAX_VALUE_LENGTH) {
            throw new Exception(
                'Value must be less than ' . static::MAX_VALUE_LENGTH . ' characters. ' . strlen($value) . ' given.',
                'maxlength_value'
            );
        }
        $this->value = $value;
        return $this;
    }

    /**
     * Get the value
     *
     * @return String
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the description
     *
     * @param PlainText $description
     * @return self
     */
    public function setDescription(PlainText $description)
    {
        if ($description->getLength() > static::MAX_DESCRIPTION_LENGTH) {
            throw new Exception(
                'Description must be less than ' . static::MAX_DESCRIPTION_LENGTH . ' characters. ' . $description->getLength() . ' given.',
                'maxlength_description'
            );
        }
        $this->description = $description;
        return $this;
    }

    /**
     * Get the description
     *
     * @return PlainText
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set url to load in the user's browser
     *
     * @param String $url
     * @return self
     */
    public function setUrl(String $url)
    {
        if (!$this->inContext([Overflow::class])) {
            throw new Exception(
                'Url can only be set for options in overflow menus',
                'invalid_context'
            );
        }
        if (strlen($url) > static::MAX_URL_LENGTH) {
            throw new Exception(
                'Url must be less than ' . static::MAX_URL_LENGTH . ' characters. ' . strlen($url) . ' given.',
                'maxlength_url'
            );
        }
        $this->url = $url;
        return $this;
    }

    /**
     * Get url to load in the user's browser
     *
     * @return String
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Is the option within onen of the given contexts?
     *
     * @param Array $contexts
     * @return Bool
     */
    private function inContext(array $contexts)
    {
        return !in_array($this->getContext(), $contexts);
    }

    /**
     * Get data for export / render
     *
     * @return stdClass
     */
    public function export()
    {
        $data = new stdClass();

        $data->text = Payload::convert($this->getText());
        $data->value = Payload::convert($this->getValue());

        if (!is_null($this->getDescription())) {
            $data->description = Payload::convert($this->getDescription());
        }

        if (!is_null($this->getUrl())) {
            $data->url = Payload::convert($this->getUrl());
        }

        return $data;
    }
}
