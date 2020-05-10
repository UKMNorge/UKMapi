<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Composition\PlainText;
use UKMNorge\Slack\Block\Structure\Element;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

/**
 * Button element
 * 
 * @see https://api.slack.com/reference/block-kit/block-elements#button
 */
class Button extends Element
{
    const TYPE = 'button';
    const REQUIRE_ACTION_ID = true;

    const MAX_TEXT_LENGTH = 75;
    const MAX_URL_LENGTH = 3000;
    const MAX_VALUE_LENGTH = 2000;
      
    public $text;
    public $url;
    public $value;
    public $style;
    public $confirm;

    /**
     * Create button element
     *
     * @param PlainText $text
     * @param String $action_id
     */
    public function __construct(String $action_id, PlainText $text)
    {
        $this->setText($text);
        $this->setActionId($action_id);
    }

    /**
     * Set text
     *
     * @param PlainText max 75 chars
     * @return self
     */
    public function setText(PlainText $text)
    {
        if ($text->getLength() > static::MAX_TEXT_LENGTH) {
            throw new Exception(
                'Maximum button text length is ' . static::MAX_TEXT_LENGTH . ' characters. ' . $text->getLength() . ' given',
                'maxlength_button_text'
            );
        }
        $this->text = $text;
        return $this;
    }

    /**
     * Get text
     *
     * @return void
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set url to load in user's browser
     *
     * @param String max 3000 chars
     * @return self
     */
    public function setUrl(String $url)
    {
        if (strlen($url) > static::MAX_URL_LENGTH) {
            throw new Exception(
                'Max url length is '. static::MAX_URL_LENGTH.' characters. ' . strlen($url) . ' given',
                'maxlength_url'
            );
        }
        $this->url = $url;
        return $this;
    }

    /**
     * Get url to load in users's browser
     * 
     * @return String
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set interaction payload value
     * 
     * @param String $value
     * @return self
     */
    public function setValue(String $value)
    {
        if (strlen($value) > static::MAX_VALUE_LENGTH) {
            throw new Exception(
                'Max value length is '. static::MAX_VALUE_LENGTH .' characters. ' . strlen($value) . ' given',
                'maxlength_value'
            );
        }
        $this->value = $value;
        return $this;
    }

    /**
     * Get interaction payload value
     *
     * @return String
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set style
     * 
     * If given default, the value will be set to null, and not sent to slack
     * @see UKMNorge\Slack\Block\Structure\ExportData
     *
     * @param String primary|danger|default
     * @return self
     */
    public function setStyle(String $style)
    {
        if (in_array($style, ['primary', 'danger'])) {
            $this->style = $style;
        } elseif ($style == 'default') {
            $this->style = null;
        }
        return $this;
    }

    /**
     * Get style
     *
     * @return String
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * Get export data
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export(); // type + action_id (if not null) + confirm (if not null)

        // Text 
        if (is_null($this->getText()) || $this->getText()->getLength() == 0) {
            throw new Exception('Button text must be set', 'missing_button_text');
        }
        $data->text = Payload::convert($this->getText());

        // Url
        if (!is_null($this->getUrl())) {
            $data->url = Payload::convert($this->getUrl());
        }

        // Value 
        if (!is_null($this->getValue())) {
            $data->value = Payload::convert($this->getValue());
        }

        // Style {
        if (!is_null($this->getStyle())) {
            $data->style = Payload::convert($this->getStyle());
        }

        return $data;
    }
}
