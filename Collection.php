<?php

namespace UKMNorge;

use Iterator;
use Exception;

abstract class Collection implements Iterator
{
    private $var = array();
    public $id = null;
    private $loaded = false;
    
    public function add( $item ) {
        // Denne må bruke find, og ikke har,
        // da har kjører doLoad, og doLoad kjører
        // add, som kjører har() (infinite loop, altså)
        if( !$this->find($item) ) {
            $this->var[] = $item;
        }
	    return $this;
    }

    public function leggTil( $item ) {
        $this->add( $item );
    }
    public function fjern( $item ) {
        $this->remove( $item );
    }
    
    public function har( $object ) {
        $this->_doLoad();
        if( is_string( $object ) ) {
            return $this->find( $object );
        }
	    return $this->find( $object->getId() );
    }

    public function get( $id ) {
        return $this->find( $id );
    }

    /**
     * Finn objekt 
     *
     * @param Any $id
     * @return Item
     */
    public function find( $id ) {
        if( !is_string( $id ) ) {
            $id = $id->getId();
        }
	    foreach( $this as $item ) {
		    if( $id == $item->getId() ) {
			    return $item;
		    }
	    }
	    return false;
    }
    
    /**
     * Hent alle
     *
     * @return Array
     */
    public function getAll() {
        $this->_doLoad();
	    return $this->var;
    }

    private function _doLoad() {
        if( $this->loaded ) {
            return true;
        }
        if( method_exists( get_called_class(), '_load' ) ) {
            $this->_load();
        }
        $this->loaded = true;
        return true;
    }
    
    /**
     * Antall elementer i collection
     *
     * @return Int $antall
     */
    public function getAntall() {
	    return sizeof( $this->getAll() );
    }
    
    public function remove( $id ) {
        if( is_object( $id ) ) {
            $id = $id->getId();
        }
    
        foreach( $this->getAll() as $key => $val ) {
            if( $id == $val->getId() ) {
                unset( $this->var[ $key ] );
                return true;
            }
        }
        throw new Exception('Could not find and remove '. $id, 110001 );
    }
    
    public function first() {
		if( isset( array_values( $this->var )[ 0 ] ) ) {
			return array_values( $this->var )[0];
		}
    }
    public function last() {
	    // TODO: Untested!!!!
	    return array_slice($this->var, -1)[0];
    }
    
    
    public function count() {
	    return sizeof( $this->var );
    }

    public function rewind()
    {
        reset($this->var);
    }
  
    public function current()
    {
        $var = current($this->var);
        return $var;
    }
  
    public function key() 
    {
        $var = key($this->var);
        return $var;
    }
  
    public function next() 
    {
        $var = next($this->var);
        return $var;
    }
  
    public function valid()
    {
        $key = key($this->var);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }

    public function __construct()
    {}

    /**
     * Hent Collection ID
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sett collection ID
     *
     * @return  self
     */ 
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}