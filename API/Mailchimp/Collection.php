<?php

namespace UKMNorge\API\Mailchimp;

use Exception;
use UKMNorge\Collection as UKMNorgeCollection;

abstract class Collection extends UKMNorgeCollection {
    var $loaded = false;

    abstract function createFromAPIData( $row );

    public function getAll() {
        if( !$this->loaded ) {
            $this->loadAll();
            $this->loaded = true;
        }
        return parent::getAll();
    }
    /**
     * Request all audiences from Mailchimp
     *
     * @return void
     */
    public function loadAll() {
        $result = Mailchimp::sendGetRequest($this->resource);
        foreach( $result->getData()->{$this->result_key} as $row ) {
            $this->add( $this->createFromAPIData( $row ) );
        }
    }

    /**
     * Get one specific list
     * 
     * Fetches from internal cache, or requests from mailchimp
     *
     * @param String $id
     * @return Audience
     * @throws Exception if not found anywhere
     */
    public function get( $id ) {
        if( !$this->har( $id ) ) {
            $this->getAll();
        }

        if( $this->har( $id ) ) {
            return $this->find( $id );
        }

        throw new Exception(
            'Could not find '. $id,
            582005
        );
    }
}