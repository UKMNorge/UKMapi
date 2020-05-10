<?php

namespace UKMNorge\Slack\Block\Composition;

use stdClass;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

/**
 * Confirmation dialog composition
 * 
 * @see https://api.slack.com/reference/block-kit/composition-objects#confirm
 */
class Confirm
{

    const MAX_TITLE_LENGTH = 100;
    const MAX_TEXT_LENGTH = 300;
    const MAX_CONFIRM_LENGTH = 30;
    const MAX_DENY_LENGTH = 30;

    public $title;
    public $text;
    public $confirm;
    public $deny;
    public $style;

    /**
     * Create a new confirmation dialog
     *
     * @param PlainText $title
     * @param Text $text
     * @param PlainText $confirm_button
     * @param PlainText $deny_button
     */
    public function __construct( PlainText $title, Text $text, PlainText $confirm_button, PlainText $deny_button )
    {
        $this->setTitle($title);
        $this->setText($text);
        $this->setConfirm($confirm_button);
        $this->setDeny($deny_button);
    }

    /**
     * Set confirmation dialog title
     *
     * @param PlainText $text
     * @return self
     */
    public function setTitle(PlainText $text)
    {
        if ($text->getLength() > static::MAX_TITLE_LENGTH) {
            throw new Exception(
                'Title must be shorter than ' . static::MAX_TITLE_LENGTH . ' characters. ' . $text->getLength() . ' given.',
                'maxlength_title'
            );
        }
        $this->title = $text;
        return $this;
    }

    /**
     * Get confirmation dialog title
     *
     * @return PlainText
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set the text
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
     * Set confirm buttion text
     *
     * @param PlainText $text
     * @return self
     */
    public function setConfirm(PlainText $text)
    {
        if ($text->getLength() > static::MAX_CONFIRM_LENGTH) {
            throw new Exception(
                'Confirm text must be shorter than ' . static::MAX_CONFIRM_LENGTH . ' characters. ' . $text->getLength() . ' given.',
                'maxlength_title'
            );
        }
        $this->confirm = $text;
        return $this;
    }

    /**
     * Get confirm button text
     *
     * @return Text
     */
    public function getConfirm()
    {
        return $this->confirm;
    }

    /**
     * Set deny button text
     *
     * @param PlainText $text
     * @return void
     */
    public function setDeny(PlainText $text)
    {
        if ($text->getLength() > static::MAX_DENY_LENGTH) {
            throw new Exception(
                'Deny text must be shorter than ' . static::MAX_DENY_LENGTH . ' characters. ' . $text->getLength() . ' given.',
                'maxlength_title'
            );
        }
        $this->deny = $text;
        return $this;
    }

    public function getDeny()
    {
        return $this->confirm;
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
     * Get data for export / render
     *
     * @return stdClass
     */
    public function export()
    {
        $data = new stdClass();
        $data->title = Payload::convert($this->getTitle());
        $data->text = Payload::convert($this->getText());
        $data->confirm = Payload::convert($this->getConfirm());
        $data->deny = Payload::convert($this->getDeny());

        if( !is_null($this->getStyle())) {
            $data->style = Payload::convert($this->getStyle());
        }
        return $data;
    }
}
