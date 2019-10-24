<?php

namespace UKMNorge\API\Mailchimp;

use stdClass;
use Exception;

class Result {
    
var $data;
var $page;

    public function __construct( stdClass $data, Int $page=null )
    {

        // TODO: If serious mailchimp error: throw it here
        if( $data->status == '404' ) {
            throw new Exception(
                'Invalid Mailchimp result: '. $data->detail
            );
        }
        
        $this->data = $data;
        $this->page = $page;
    }
    
    /**
	 * Gets the raw result object
	 * @return stdClass
	 */
	public function getData() {
		if(empty( $this->data ) ) {
			throw new Exception("No result found");
		}

		return $this->data;
    }
    
    /**
     * If there were errors - return them
     *
     * @return boolean
     */
    public function hasError() {
        return isset( $this->getData()->errors ) && is_array( $this->getData()->errors ) && sizeof( $this->getData()->errors ) > 0;
    }

    public function getError() {
        if( !$this->hasError() ) {
            throw new Exception(
                'Cannot get error where there is none'
            );
        }
        return $this->getData()->errors;
    }
	
	/**
	 * Returns an array of all failed updates
	 * @return Array
	 */
	public function getFailedUpdates() {
		return $this->getData()->errors;
	}

	/**
	 * Returns the amount of fields that failed to update
	 * @return int
	 */
	public function getTotalFailed() {
		//return $this->getData()->
	}

	/**
	 * Returns the amount of newly created fields
	 * @return int
	 */
	public function getTotalCreated() {
        //return $this->getData()->
	}
	
	/**
	 * Returns the amount of updated fields
	 * @return int
	 */
	public function getTotalUpdated() {
		//return $this->getData()->
	}
}