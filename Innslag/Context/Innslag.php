<?php

namespace UKMNorge\Innslag\Context;

use UKMNorge\Innslag\Type;

require_once('UKM/Autoloader.php');

class Innslag {
	var $id = null;
	var $type = null;
    
    /**
     * Opprett ny Innslag-context
     *
     * @param Int $id
     * @param Type $type
     */
	public function __construct( Int $id, String $type ) {
		$this->id = $id;
		$this->type = $type;
	}

    /**
     * Sett innslagets ID
     *
     * @param Int $id
     * @return self
     */
	public function setId(Int $id ) {
		$this->id = $id;
		return $this;
    }
    
    /**
     * Hent innslagets ID
     *
     * @return Int $id
     */
	public function getId() {
		return $this->id;
	}
    
    /**
     * Sett innslagets type
     *
     * @param String $type
     * @return self
     */
	public function setType( Type $type ) {
		$this->type = $type;
		return $this;
    }
    
    /**
     * Hent innslagets type
     *
     * @return String
     */
	public function getType() {
		return $this->type;
    }
    
    /**
     * Sjekk at gitt objekt er gyldig Context\Innslag-objekt
     *
     * @param Any $object
     * @return Bool
     */
    public static function validateClass( $object ) {
        return is_object($object) && get_class($object) == 'UKMNorge\Innslag\Context\Innslag';
    }
}