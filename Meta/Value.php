<?php

namespace UKMNorge\Meta;

use UKMNorge\Database\SQL\Query;
require_once('UKM/Autoloader.php');

class Value {

    private $parent;
    private $key;
    private $value;
    private $value_raw;
    private $id;

    public function __construct( ParentObject $parent, String $key, String $json_value, $existing_id ) {
        $this->parent = $parent;
        $this->key = $key;
        if( empty( $json_value ) ) {
            $this->value = false;
            $this->value = json_encode( false );
        } else {
            $this->value = json_decode( $json_value );
            $this->value_raw = $json_value;
        }
        $this->id = $existing_id;
    }

    /**
     * Last inn metadata fra database
     *
     * @param ParentObject $parent
     * @param String $key
     * @return Value
     */
    public static function loadFromKey( ParentObject $parent, String $key ) {
        $sql = new Query(
            "SELECT `value`, `id`
            FROM `ukm_meta`
            WHERE `parent_type` = '#parent_type'
            AND `parent_id` = '#parent_id'
            AND `name` = '#key'",
            [
                'parent_type' => $parent->getType(),
                'parent_id' => $parent->getId(),
                'key' => $key
            ]
        );
    
        return static::loadFromData( $parent, $key, $sql->getArray() );
    }

    /**
     * Last inn metadata fra rådata
     *
     * @param ParentObject $parent
     * @param String $key
     * @param Array $data
     * @return Value
     */
    public static function loadFromData( ParentObject $parent, String $key, Array $data = null) {
        if( null == $data ) {
            $data = [
                'value' => json_encode(null),
                'id' => 0
            ];
        }

        return new Value( $parent, $key, $data['value'], $data['id'] );
    }


    /**
     * Hent den faktiske verdien
     * 
     * @return Any $value
     */ 
    public function getValue()
    {
        return $this->value;
    }

   /**
     * Sett ny verdi
     * 
     * @param Any $value
     * @return Value $this
     */ 
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @alias setValue
     */
    public function set( $value ) {
        return $this->setValue($value);
    }

    /**
     * Hvis kastet til string, returner verdien
     *
     * @return string
     */
    public function __toString() {
        if( !is_string( $this->getValue() ) ) {
            return 'Kunne ikke konvertere verdien til string. Rå-data: '. $this->getAsJson();
        }
        return $this->getValue();
    }

    /**
     * Hent parent-objektet
     * 
     * @return ParentObject $parent;
     */ 
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Hent verdiens nøkkel (navn)
     * 
     * @return String $key;
     */ 
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Hent rå-verdien fra databasen
     * 
     * @return String $json_data
     */ 
    public function getValue_raw()
    {
        return $this->value_raw;
    }

    /**
     * Hent nåværende verdi som JSON-string
     *
     * @return String $json_data
     */
    public function getAsJson() {
        return json_encode( $this->getValue() );
    }

    /**
     * Hent database-ID for verdien
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Finnes verdien i databasen?
     *
     * @return void
     */
    public function eksisterer() {
        return is_numeric( $this->getId() ) && $this->getId() > 0;
    }

    /**
     * Sett ny verdi
     * Skal i teorien kun brukes av Write-klassen
     * 
     * @param Int $id
     * @return Value $this
     */ 
    public function setId( Int $id)
    {
        $this->id = $id;

        return $this;
    }
}