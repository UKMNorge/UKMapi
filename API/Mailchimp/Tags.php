<?php

namespace UKMNorge\API\Mailchimp;

use UKMNorge\API\Mailchimp\Collection;
use UKMNorge\Database\SQL\Query;
use Exception;

class Tags extends Collection {
    public $resource;
    public $result_key;
    public $audience_id;

    /**
     * Set audience ID
     * 
     * Must be run before API requests. Is handled automatically when you run
     * $audience->getTags()
     *
     * @param String $audience_id
     * @return void
     */
    public function setAudienceId( String $audience_id ) {
        $this->audience_id = $audience_id;
        $this->resource = '/lists/'.$this->audience_id.'/segments';
        $this->result_key = 'segments';
    }
 
    /**
     * Create a tag
     *
     * @param String $name
     * @return Tag
     */
    public function create( String $name ) {
        return Tag::createTag( $this->getAudienceId(), $name );
    }

    /**
     * Create a tag instance from API data
     *
     * @param stdClass $row
     * @return 
     */
    public function createFromAPIData($row)
    {
        return Tag::createFromAPIdata( $row );
    }

    /**
     * Create a tag instance from database data
     *
     * @param Array $row
     * @return Tag
     */
    public function createFromDBdata( $row ) {
        return Tag::createFromDBdata( $row );
    }

    /**
     * Get ID of audience
     *
     * @return String
     */
    public function getAudienceId() {
        return $this->audience_id;
    }

    /**
     * Get or create the tag
     * If you need it - we fix it
     *
     * @param String $tag_name
     * @return Tag the one you want
     * @throws Exception out-of-luck-exception if it all went wrong
     */
    public function getOrCreate( String $tag_name ) {
        try {
            return $this->get($tag_name);
        } catch( Exception $e ) {
            if( $e->getCode() == 582005 ) {
                return $this->create($tag_name);
            }
        }
        throw $e;
    }

    /**
     * Get one specific tag
     * 
     * Fetches from internal cache, or requests from mailchimp
     *
     * @param String $id
     * @return Tag
     * @throws Exception if not found anywhere
     */
    public function get( $id ) {
        $id = Tag::sanitize($id);
        // Hvis ikke i lokal cache, sjekk om den finnes i databasen
        if( !$this->har( $id ) ) {
            $this->getAllFromDB();
        }

        // Hvis ikke i databasen, sjekk om den finnes i mailchimp
        if( !$this->har( $id ) ) {
            $this->getAll();
        }

        // Nå burde vi ha den, hvis ikke er alt håp ute (les: kjør create)
        if( $this->har( $id ) ) {
            return $this->find( $id );
        }

        throw new Exception(
            'Could not find tag '. $id,
            582005
        );
    }

    /**
     * Fetch all audience tags from database
     *
     * @return void
     */
    public function getAllFromDB() {
        $query = new Query(
            "SELECT * 
            FROM `mailchimp_tag`
            WHERE `audience_id` = '#audience'",
            [
                'audience' => $this->getAudienceId()
            ]  
        );

        $tags = $query->run();

        while( $tagdata = Query::fetch( $tags ) ) {
            $this->add( Tag::createFromDBdata( $tagdata ) );
        }
    }
}