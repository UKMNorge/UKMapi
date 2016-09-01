<?php

abstract class Collection implements Iterator
{
    private $var = array();
    
    public function add( $item ) {
	    $this->var[] = $item;
	    return $this;
    }

    public function find( $id ) {
	    foreach( $this as $item ) {
		    if( $id == $item->getId() ) {
			    return $item;
		    }
	    }
	    return false;
    }
    
    public function remove( $id ) {
		if( false == $this->find( $id ) ) {
			throw new Exception('Could not find '. $id );
		}
		throw new Exception('IMPLEMENT remove');
    }
    
    public function first() {
	    return array_values( $this->var )[0];
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
}