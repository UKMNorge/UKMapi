<?php

namespace UKMNorge\Log;

class User {
    var $system_id = null;
    var $user_id = null;

    public function __construct( Array $data ) {
        $this->system_id = $data['log_system_id'];
        $this->user_id = $data['log_u_id'];
    }

    /**
     * Get the value of system_id
     */ 
    public function getSystem()
    {
        return $this->system_id;
    }

    /**
     * Get the value of user_id
     */ 
    public function getId()
    {
        return $this->user_id;
    }
}