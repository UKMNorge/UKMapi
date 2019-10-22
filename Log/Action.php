<?php

namespace UKMNorge\Log;

use Exception;
use UKMNorge\Database\SQL\Query;

class Action {
    var $id = null;
    var $verb = null;
    var $element = null;
    var $datatype = null;
    var $identifier = null;
    var $print = false;

    public static function loadFromData( Array $data ) {
        return new Action( $data );
    }

    public static function loadFromId( Int $id ) {
        $sql = new Query("SELECT * 
            FROM `log_actions`
            WHERE `log_action_id` = '#id'",
            ['id' => $id]
        );
        
        $res = $sql->run('array');

        if( !$res ) {
            throw new Exception(
                'Beklager, fant ikke log action '. $id
            );
        }

        return new Action( $res );

    }

    public function __construct($data) {
        $this->id = $data['log_action_id'];
        $this->verb = $data['log_action_verb'];
        $this->element = $data['log_action_element'];
        $this->datatype = $data['log_action_datatype'];
        $this->identifier = $data['log_action_identifier'];
        $this->print = $data['log_action_printobject'] == '1';
    }

    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of verb
     */ 
    public function getVerb()
    {
        return $this->verb;
    }

    /**
     * Get the value of element
     */ 
    public function getElement()
    {
        return $this->element;
    }

    /**
     * Get the value of datatype
     */ 
    public function getDatatype()
    {
        return $this->datatype;
    }

    /**
     * Get the value of identifier
     */ 
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Get the value of print
     */ 
    public function getPrint()
    {
        return $this->print;
    }
}