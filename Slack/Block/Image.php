<?php

namespace UKMNorge\Slack\Block;

use stdClass;
use UKMNorge\Slack\Block\Structure\Block;
use UKMNorge\Slack\Payload\Payload;
use UKMNorge\Slack\Block\Structure\Exception;

/**
 * Image block
 * 
 * @see https://api.slack.com/reference/block-kit/blocks#image
 */
class Image extends Block {
    public $type = 'image';
    public $url;
    public $alt_text;
    public $title;

    /**
     * Create new image block
     *
     * @param String $image_url
     * @param String $alt_text
     */
    public function __construct( String $image_url, String $alt_text )
    {
        $this->setUrl($image_url);
        $this->setAltText($alt_text);
    }

    /**
     * Set image url
     *
     * @param String $image_url
     * @return self
     */
    public function setUrl( String $image_url ) {
        if( strlen($image_url) > 3000 ) {
            throw new Exception('Image url maxlength is 3000. Given image url length is '. strlen($image_url), 'maxlength_image_url');
        }
        $this->url = $image_url;
        return $this;
    }

    /**
     * Get image url
     *
     * @return String
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Set image alt text
     *
     * @param String $alt_text
     * @return self
     */
    public function setAltText( String $alt_text ) {
        if( strlen($alt_text) > 2000 ) {
            throw new Exception('Image alt text maxlength is 2000. Given alt text lenght is '. strlen($alt_text), 'maxlength_image_alt_text');
        }
        $this->alt_text = $alt_text;
        return $this;
    }

    /**
     * Get image alt text
     *
     * @return String
     */
    public function getAltText() {
        return $this->alt_text;
    }

    /**
     * Set image title
     *
     * @param Composition\Text $title
     * @return self
     */
    public function setTitle( Composition\Text $title ) {
        if( $title->length() > 2000 ){
            throw new Exception('Image title maxlength is 2000. Given title length is '. $title->length(), 'maxlength_image_title');
        }
        $this->title = $title;
        return $this;
    }

    /**
     * Get image title
     *
     * @return Composition\Text
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Export object
     *
     * @return stdClass
     */
    public function export() {
        $data = parent::export();

        // Url
        if( is_null( $this->getUrl() ) ) {
            throw new Exception('Missing required image url', 'missing_image_url');
        }
        $data->image_url = Payload::convert($this->getUrl());
        
        // Alt text
        if( is_null( $this->getAltText() ) ) {
            throw new Exception('Missing required image alternate text', 'missing_image_alt_text');
        }
        $data->alt_text = Payload::convert($this->getAltText());
    
        // Alt text
        if( !is_null( $this->getTitle() ) ) {
            $data->title = Payload::convert($this->getTitle());
        }

        return $data;
    }
}