<?php

namespace UKMNorge\Innslag\Context;

use Exception;
use UKMNorge\Innslag\Personer\Person;

require_once('UKM/Autoloader.php');


class Context {
    var $type = null;
    
    var $sesong = null;
	var $monstring = null;
	var $innslag = null;
	var $forestilling = null;
	var $videresend_til = false;
    var $kontaktperson = null;
    var $delta_user_id = null;
    
	public static function createMonstring( $id, $type, $sesong, $fylke, $kommuner ) {
		$context = new Context( 'monstring' );
		$context->monstring = new Monstring( $id, $type, $sesong, $fylke, $kommuner );
		return $context;
	}
	
	public static function createInnslag( $id, $type, $monstring_id, $monstring_type, $monstring_sesong) {
		$context = new Context( 'innslag' );
		$context->monstring = new Monstring( $monstring_id, $monstring_type, $monstring_sesong, false, false );
		$context->innslag = new Innslag( $id, $type );
		return $context;
	}
	
	public static function createForestilling( $id, $context_monstring=false ) {
		$context = new Context( 'forestilling' );
		$context->forestilling = new Forestilling( $id );
		if( $context_monstring !== false && get_class( $context_monstring ) == 'context_monstring' ) {
			$context->monstring = $context_monstring;
		}
		return $context;
    }

    public static function createKontaktperson( Person $kontaktperson, Int $sesong ) {
        $context = new Context('kontaktperson');
        $context->kontaktperson = new Kontaktperson( $kontaktperson->getId() );
        $context->sesong = $sesong;
        return $context;
    }

    public static function createDeltaUser( Int $user_id , Int $sesong ) {
        $context = new Context('deltauser');
        $context->sesong = $sesong;
        $context->delta_user_id = $user_id;
        return $context;
    }
    
    public static function createSesong( $sesong ) {
        $context = new Context( 'sesong' );
        $context->sesong = $sesong;
        return $context;
    }
	
	public function __construct( $type ) {
		$this->type = $type;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function getMonstring() {
		return $this->monstring;
	}
	public function getInnslag() {
		return $this->innslag;
	}
	public function getForestilling() {
		return $this->forestilling;
    }
    public function getKontaktperson() {
        return $this->kontaktperson;
    }
    public function getDeltaUserId() {
        return $this->delta_user_id;
    }
    
    /**
     * Hvilken sesong er etterspurt?
     */
    public function getSesong() {
        switch( $this->getType() ) {
            case 'deltauser':
            case 'kontaktperson':
            case 'sesong':
                return $this->sesong;
            case 'forestilling':
            case 'monstring':
                return $this->getMonstring()->getSesong();
            case 'innslag':
                if( $this->getMonstring() !== null ) {
                    return $this->getMonstring()->getSesong();
                }
            default:
                throw new Exception(
                    'CONTEXT: Denne typen context ('. $this->getType() .') støtter ikke getSesong()',
                    112001
                );
        }
    }
	
	/**
	 * Hvis innslaget er hentet ut som en del av en innslag-collection,
	 * og funksjonen getVideresendte() er kjørt, settes dette på innslagets
	 * kontekst, slik at det kan brukes på hentPersoner
	**/
	public function getVideresendTil() {
		return $this->videresend_til;
	}
	public function setVideresendTil( $monstring ) {
		if( is_object( $monstring ) && in_array(get_class( $monstring ),['UKMNorge\Arrangement\Arrangement', 'monstring_v2']) ) {
			$monstring = $monstring->getId();
		}
		$this->videresend_til = $monstring;
	}
}