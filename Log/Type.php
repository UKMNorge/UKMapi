<?php

namespace UKMNorge\Log;

class Type
{
    var $id = null;
    var $navn = null;
    var $tabell = null;
    var $primary_key = null;

    public function __construct(array $data)
    {
        $this->id = $data['log_object_id'];
        $this->navn = $data['log_object_name'];
        $this->tabell = $data['log_object_table'];
        $this->primary_key = $data['log_object_table_idcol'];
    }

    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the value of navn
     */ 
    public function getNavn()
    {
        return $this->navn;
    }

    /**
     * Get the value of tabell
     */ 
    public function getTabell()
    {
        return $this->tabell;
    }

    /**
     * Get the value of primary_col
     */ 
    public function getPrimaryKey()
    {
        return $this->primary_key;
    }
}
