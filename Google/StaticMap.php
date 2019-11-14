<?php

namespace UKMNorge\Google;

class StaticMap {

    var $lat = null;
    var $lon = null;
    var $address = null;
    var $name = null;
    var $map = null;
    var $link = null;
    static $api_key = null;

    public static function fromJSON( $json ) {
        $map = new StaticMap();
        $map->setLat( $json->lat );
        $map->setLon( $json->lon );
        $map->setAddress( $json->address );
        $map->setName( $json->name );
        $map->setMap( $json->map );
        $map->setLink( $json->link );
        return $map;
    }

    public static function fromPOST( $post_name ) {
        $map = new StaticMap();
        foreach( static::getPOSTFields() as $key ) {
            $function = 'set'.ucfirst($key);
            $map->$function( $_POST[$post_name .'_'. $key] );
        }
        return $map;
    }
    
    public static function getPOSTFields() {
        return [
            'lat',
            'lon',
            'address',
            'name',
            'map',
            'link'
        ];
    }

    /**
     * Sett API-key
     *
     * @param String $api_key
     * @return void
     */
    public static function setApiKey( String $api_key ) {
        static::$api_key = $api_key;
    }

    public function toJSON() {
        return json_encode(
            $this->getPOSTValues()
        );
    }
    
    public function getPOSTValues() {
        $values = [];
        foreach( static::getPOSTFields() as $key ) {
            $function = 'get'.ucfirst($key);
            $values[ $key ] = $this->$function();
        }
        return $values;
    }



    /**
     * Get the value of lat
     */ 
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * Set the value of lat
     *
     * @return  self
     */ 
    public function setLat($lat)
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * Get the value of lon
     */ 
    public function getLon()
    {
        return $this->lon;
    }

    /**
     * Set the value of lon
     *
     * @return  self
     */ 
    public function setLon($lon)
    {
        $this->lon = $lon;

        return $this;
    }

    /**
     * Get the value of address
     */ 
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set the value of address
     *
     * @return  self
     */ 
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get the value of name
     */ 
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @return  self
     */ 
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Do we actually have a map?
     *
     * @return Bool
     */
    public function hasMap() {
        return !empty( $this->map );
    }

    /**
     * Get the value of map
     */ 
    public function getMap()
    {
        if( null == $this->map ) {
            return 'https://placehold.it/400x300?text=Ukjent%20sted';
        }
        return $this->map . (static::$api_key !== null ? '&key='. static::$api_key : '');
    }

    /**
     * Set the value of map
     *
     * @return  self
     */ 
    public function setMap($map)
    {
        $this->map = $map;

        return $this;
    }

    /**
     * Get the value of link
     */ 
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Set the value of link
     *
     * @return  self
     */ 
    public function setLink($link)
    {
        $this->link = $link;

        return $this;
    }
}