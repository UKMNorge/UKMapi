<?php

namespace UKMNorge\Nettverk;
use UKMNorge\Wordpress\User;

require_once('UKM/Wordpress/User.class.php');

class Administrator
{

    private $wp_user_id = 0;
    private $user = null;

    public function __construct( Int $wp_user_id)
    {
        $this->wp_user_id = $wp_user_id;
    }


    private function _load()
    {        
        $this->user = new User( $this->getId() );
    }

    /**
     * Get the value of user
     */
    public function getUser()
    {
        if ($this->user == null) { 
            $this->_load();
        }
        return $this->user;
    }

    /**
     * Get the value of wp_user_id
     */ 
    public function getId()
    {
        return $this->wp_user_id;
    }

    public function getNavn() {
        return $this->getUser()->getNavn();
    }
}
