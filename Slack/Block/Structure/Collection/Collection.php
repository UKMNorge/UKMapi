<?php

namespace UKMNorge\Slack\Block\Structure\Collection;

use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

class Collection {
    public $elements = [];
    public $maxlength;
    public $supported_classes;

    /**
     * Initiate collection
     *
     * @param Int $maxlength
     */
    public function __construct( Int $maxlength ) 
    {
        if( !is_null($maxlength) && $maxlength != 0 ) {
            $this->maxlength = $maxlength;
        }
    }

    /**
     * Add a field
     *
     * @param $element
     * @throws Exception
     * @return self
     */
    public function add( $element ) {
        $this->validateLength( $this->getLength()+1 );
        $this->validateSupported( $element );
        $this->elements[] = $element;
        return $this;
    }

    /**
     * Set all fields to given array
     *
     * @param Array $fields
     * @return self
     */
    public function set( Array $elements ) {
        $this->validateLength(sizeof($elements));
        foreach( $elements as $element ) {
            $this->validateSupported( $element );
        }
        $this->elements = $elements;
        return $this;
    }

    /**
     * Get all fields
     *
     * @return Array
     */
    public function getAll() {
        return $this->elements;
    }

    /**
     * Get number of collection elements
     *
     * @return Int
     */
    public function getLength() {
        return sizeof( $this->elements );
    }
    
    /**
     * Get allowed max length
     *
     * @return Int
     */
    public function getMaxLength() {
        return $this->maxlength;
    }

    /**
     * Add supported classes
     *
     * @param Array<String> $supported
     * @return self
     */
    public function setSupportedClasses( Array $supported ) {
        $this->supported_classes = $supported;
        return $this;
    }

    /**
     * Get all supported classes
     * 
     * null == all allowed
     *
     * @return Array|null
     */
    public function getSupportedClasses() {
        return $this->supported_classes;
    }

    /**
     * Get current type (set by child)
     *
     * @return String
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Make sure we stay within the maximum allowed elements limit
     *
     * @param Int $current_length
     * @return Bool
     * @throws Exception
     */
    private function validateLength( $current_length ) {
        if( is_null($this->maxlength)) {
            return true;
        }
        if( $current_length > $this->getMaxLength() ) {
            throw new Exception('Max number of elements is '. $this->getMaxLength() .' for '. $this->getType() , 'maxlength_fields');
        }
        return true;
    }

    /**
     * Make sure element is within collection allowed element types
     *
     * @param mixed $element
     * @return Bool true
     * @throws Exception
     */
    private function validateSupported( $element ) {
        if( is_null($this->getSupportedClasses() ) ) {
            return true;
        }
        if( in_array(get_class($element), $this->getSupportedClasses())) {
            return true;
        }
        throw new Exception(
            'Could not add element to collection. Unsupported class type '. static::class .
            ' in collection which supports '. join(', ', $this->getSupportedClasses()),
            'invalid_element_type'
        );
    }

    /**
     * Export collection
     *
     * @return Array
     */
    public function export() {
        $data = [];
        
        foreach( $this->getAll() as $element ) {
            $data[] = $element->export();
        }
        
        return $data;
    }
}

