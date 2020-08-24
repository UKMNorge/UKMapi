<?php

namespace UKMNorge\Slack\Cache;

class Bot {

    private $id;
    private $access_token;

    public function __construct( String $id, String $access_token )
    {
        $this->id = $id;
        $this->access_token = $access_token;
    }


    /**
     * Hent bot'ens access token
     * 
     * @return String
     */ 
    public function getAccessToken()
    {
        return $this->access_token;
    }

    /**
     * Hent bot'ens id
     * 
     * @return String
     */ 
    public function getId()
    {
        return $this->id;
    }
}