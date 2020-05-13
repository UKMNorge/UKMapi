<?php

namespace UKMNorge\Slack\Payload;

use stdClass;
use UKMNorge\Slack\Block\Composition\PlainText;
use UKMNorge\Slack\Block\Structure\Exception;

class Modal extends Home {

    const TYPE = 'modal';
    
    const MAX_TITLE_LENGTH = 24;
    const MAX_CLOSE_LENGTH = 24;
    const MAX_SUBMIT_LENGTH = 24;

    public $title;
    public $close;
    public $submit;
    public $clear_on_close;
    public $notify_on_close;

    public function __construct( PlainText $title )
    {
        $this->setTitle($title);
    }
    
    /**
     * Set title
     *
     * @param PlainText $title
     * @return self
     */
    public function setTitle( PlainText $title ) {
        if( $title->getLength() > static::MAX_TITLE_LENGTH ) {
            throw new Exception(
                'Title must be less than '. static::MAX_TITLE_LENGTH .' characters. Currently '. $title->getLength(),
                'maxlength_title'
            );
        }
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return PlainText
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Set close button text
     *
     * @param PlainText $text
     * @return self
     */
    public function setClose( PlainText $text ) {
        if( $text->getLength() > static::MAX_CLOSE_LENGTH ) {
            throw new Exception(
                'Close button text must be less than '. static::MAX_CLOSE_LENGTH .' characters. Currently '. $text->getLength(),
                'maxlength_close'
            );
        }
        $this->close = $text;
        return $this;
    }

    /**
     * Get close button text
     *
     * @return PlainText
     */
    public function getClose() {
        return $this->close;
    }

    /**
     * Set submit button text
     *
     * @param PlainText $text
     * @return self
     */
    public function setSubmit( PlainText $text ) {
        if( $text->getLength() > static::MAX_SUBMIT_LENGTH ) {
            throw new Exception(
                'Close button text must be less than '. static::MAX_SUBMIT_LENGTH .' characters. Currently '. $text->getLength(),
                'maxlength_close'
            );
        }
        $this->submit = $text;
        return $this;
    }

    /**
     * Get submit button text
     *
     * @return PlainText
     */
    public function getSubmit() {
        return $this->submit;
    }

    /**
     * Set clear all view upon close
     *
     * @param Bool $status
     * @return self
     */
    public function setClearOnClose( Bool $status ) {
        $this->clear_on_close = $status;
        return $this;
    }

    /**
     * Get whether to clear all view upon close
     *
     * @return Bool
     */
    public function getClearOnClose() {
        return $this->clear_on_close;
    }

    /**
     * Set whether slack should notify request_url upon close
     *
     * @param Bool $status
     * @return self
     */
    public function setNotifyOnClose( Bool $status ) {
        $this->notify_on_close = $status;
        return $this;
    }

    /**
     * Get whether slack should notify request_url upon close
     *
     * @return Bool
     */
    public function getNotifyOnClose() {
        return $this->notify_on_close;
    }

    /**
     * Return export data
     *
     * @return stdClass
    */
    public function export() {
        $data = parent::export();
        
        // Title
        $data->title = Payload::convert($this->getTitle()->export());
        
        // Close
        if( !is_null($this->getClose())) {
            $data->close = Payload::convert($this->getClose()->export());
        }

        // Submit
        if( !is_null($this->getSubmit())) {
            $data->submit = Payload::convert($this->getSubmit()->export());
        }

        // Clear on close
        if( !is_null($this->getClearOnClose())) {
            $data->clear_on_close = Payload::convert($this->getClearOnClose());
        }

        // Notify on close
        if( !is_null($this->getNotifyOnClose())) {
            $data->notify_on_close = Payload::convert($this->getNotifyOnClose());
        }

        return $data;
    }
}