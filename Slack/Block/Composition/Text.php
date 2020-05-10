<?php


namespace UKMNorge\Slack\Block\Composition;

use stdClass;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

/**
 * Abstract text object
 * 
 * Typehinting usually utilizes Text for objects supporting both types, 
 * or PlainText for object supporting only plain text
 * 
 * @see Plaintext or Markdown
 * @see https://api.slack.com/reference/block-kit/composition-objects#text
 */
abstract class Text
{
    public $text;
    public $emoji;
    public $verbatim;

    public function __construct(String $text)
    {
        $this->setText($text);
    }

    /**
     * Set the actual text
     *
     * @param String $text
     * @return self
     */
    public function setText( String $text ) {
        $this->text = $text;
        return $this;
    }

    /**
     * Get the actual text
     *
     * @return String
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Get length of text
     *
     * @return Int
     */
    public function getLength()
    {
        return strlen($this->text);
    }

    /**
     * Escape emojis to colon-emojis
     *
     * @param Bool $escape
     * @return self
     */
    public function setEscapeEmoji( Bool $escape ) {
        $this->emoji = $escape;
        return $this;
    }

    /**
     * Should emojis be escaped?
     *
     * @return Bool
     */
    public function getEscapeEmoji() {
        return $this->emoji;
    }

    /**
     * Set verbatim mode
     *
     * @param Bool $status
     * @return self
     */
    public function setVerbatim( Bool $status ) {
        $this->verbatim = $status;
        return $this;
    }

    /**
     * Get verbatim mode setting
     *
     * @return void
     */
    public function getVerbatim() {
        return $this->verbatim;
    }

    /**
     * Get type of text
     *
     * @return String plain_text|mrkdwn
     */
    public function getType()
    {
        return static::TYPE;
    }
    
    /**
     * Is this a plaintext object
     *
     * @return Bool
     */
    public function isPlainText()
    {
        return $this->getType() === 'plain_text';
    }

    /**
     * Is this a markdown object
     *
     * @return Bool
     */
    public function isMarkdown()
    {
        return $this->getType() === 'mrkdwn';
    }

    /**
     * Start ExportData object with basic data
     * 
     * Most child classes should extend this one
     *
     * @return ExportData
     */
    public function export()
    {
        $data = new stdClass();
        $data->type = Payload::convert($this->getType());
        
        // Text
        if( is_null( $this->getText()) || empty($this->getText())) {
            throw new Exception('Text objects must contain text.', 'missing_text');
        }
        $data->text = Payload::convert($this->getText());

        // Emoji
        if( !is_null($this->getEscapeEmoji())) {
            $data->emoji = Payload::convert($this->getEscapeEmoji());
        }
        
        // Verbatim
        if( !is_null($this->getVerbatim())) {
            echo 'VERBATIM IS: '. var_export($this->getVerbatim(), true);
            $data->verbatim = Payload::convert($this->getVerbatim());
        }

        return $data;
    }
    
}
