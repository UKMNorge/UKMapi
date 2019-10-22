<?php

namespace UKMNorge\Log;

use DateTime;
use UKMNorge\Arrangement\Arrangement;

class Event {
    var $id = null;
    var $pl_id = null;
    var $time = null;
    var $value = null;
    var $element = null;
    
    var $type = null;
    var $action = null;
    var $user = null;
    var $arrangement = null;
    
    
    public function __construct( $data ) {
        $this->action = Action::loadFromData($data);
        $this->user = new User($data);
        $this->type = new Type($data);

        $this->id = $data['log_id'];
        $this->element = $data['log_the_object_id'];
        $this->value = $data['log_value'];
        $this->time = new DateTime($data['log_time']);
        $this->pl_id = $data['log_pl_id'];
    }

    /**
     * Hent logg-radens id
     * 
     * @return Int $id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent arrangementet
     * @return Arrangement $arrangement
     */ 
    public function getArrangement()
    {
        if( null == $this->arrangement ) {
            $this->arrangement = new Arrangement( $this->pl_id );
        }
        return $this->arrangement;
    }

    /**
     * Tidspunkt for loggføringen
     * 
     * @return DateTime
     */ 
    public function getTidspunkt()
    {
        return $this->time;
    }

    /**
     * Hent verdien som ble lagret
     */ 
    public function getVerdi()
    {
        return $this->value;
    }

    /**
     * Hent bruker-informajson
     * 
     * @return User
     */ 
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Hent handlingens standard-data
     */ 
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get the value of object
     */ 
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the value of element
     */ 
    public function getElement()
    {
        return $this->element;
    }
}