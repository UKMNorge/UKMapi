<?php

namespace UKMNorge\Samtykke;

use SQL;
use SQLins;
use SQLdel;
use Exception;
use person_v2;
use DateTime;

require_once('UKM/samtykke/personkategori.class.php');
require_once('UKM/samtykke/status.class.php');
require_once('UKM/samtykke/kommunikasjon.collection.php');
require_once('UKM/samtykke/foresatt.class.php');
require_once('UKM/person.class.php');


class Person {
	
	var $id = null;
	var $person = null;
	
	var $alder = null;
	var $kategori = null;
	var $p_id = null;
	var $mobil = null;
	var $status = null;
	var $foresatt = null;
	var $last_change = null;
    var $antall_innslag = null;
	
	var $updates = null;
	var $kommunikasjon = null;
	
	var $attr = null;
	
	public function __construct( $person, $innslag ) {
        if( get_class( $innslag ) != 'innslag_v2' ) {
            throw new Exception(
                'Samtykke\Person krever innslag_v2 som parameter 2',
                113001
            );
        }
		$this->attr = [];
		$this->person = $person;
		$row = $this->_createIfNotExists( $person, $innslag->getSesong() );
        $this->_populate( $row );
        $this->leggTilInnslag( $innslag->getId() );
	}
	
	public static function getById( $samtykke_id ) {
		$sql = new SQL("
			SELECT `p_id`,`year`
			FROM `samtykke_deltaker`
			WHERE `id` = '#samtykke'",
			[
				'samtykke' => $samtykke_id,
			]
		);
		$row = $sql->run('array');
		
		if( !$row ) {
			throw new Exception('Beklager, kunne ikke finne samtykke #'. $samtykke_id );
		}
		
		$person = new person_v2( $row['p_id'] );
		return new Person( $person, $row['year'] );
	}
	
	public function setAttr( $key, $value ) {
		$this->attr[ $key ] = $value;
		return $this;
	}
	public function getAttr( $key ) {
		if( isset( $this->attr[ $key ] ) ) {
			return $this->attr[ $key ];
		}
		return false;
	}
	
	public function getId() {
		return $this->id;
	}
	public function getAlder() {
		return $this->getPerson()->getAlderTall();
	}
	public function getKategori() {
		return $this->kategori;
	}
	public function getPerson() {
		return $this->person;
	}
	public function getNavn() {
		return $this->person->getNavn();
	}
	public function getMobil() {
		return $this->mobil;
	}
	public function getStatus() {
		return $this->status;
	}
	public function getForesatt() {
		return $this->foresatt;
	}
	public function getLastChange() {
		return $this->last_change;
    }
    
    public function getAntallInnslag() {
        return $this->antall_innslag;
    }

    public function fjernInnslag ( $innslag_id ) {
        $this->antall_innslag--;
        
        try {
            $SQLdel = new SQLdel(
                'samtykke_deltaker_innslag',
                [
                    'p_id' => $this->getPerson()->getId(),
                    'b_id' => $innslag_id
                ]
            );
            $SQLdel->run();
        } catch( Exception $e ) {
            // Do nothing
        }
    }

    public function leggTilInnslag( $innslag_id ) {
        $this->antall_innslag++;
        try {
            $SQLins = new SQLins('samtykke_deltaker_innslag');
            $SQLins->add('p_id', $this->getPerson()->getId());
            $SQLins->add('b_id', $innslag_id);
            $SQLins->run();
        } catch( Exception $e ) {
            // Ganske vanlig å få feil på denne, pga
            // unique-constraint. Do nothing then
        }
       }
	
	public function harForesatt() {
		return $this->getForesatt()->har();
	}
	
	public function getKommunikasjon() {
		if( $this->kommunikasjon == null ) {
			$this->kommunikasjon = new Kommunikasjon( $this->getId() );
		}
		return $this->kommunikasjon;
	}
	
	
	public function setStatus( $status, $ip ) {
		$this->status = new Status( $status, new DateTime(), $ip );

		$this->_update('status', $this->getStatus()->getId());
		$this->_update('status_timestamp', $this->getStatus()->getTimestamp()->getForDatabase());
		$this->_update('status_ip', $this->getStatus()->getIp());
	}
	
	public function setForesattStatus( $status, $ip ) {
		$this->getForesatt()->status = new Status( $status, new DateTime(), $ip );

		$this->_update('foresatt_status', $this->getForesatt()->getStatus()->getId());
		$this->_update('foresatt_status_timestamp', $this->getForesatt()->getStatus()->getTimestamp()->getForDatabase());
		$this->_update('foresatt_status_ip', $this->getForesatt()->getStatus()->getIp());
	}
	
	public function setForesatt( $navn, $mobil ) {
		$this->foresatt = new Foresatt( $navn, $mobil, 'ikke_sendt', null, null );
		
		$this->_update('foresatt_navn', $navn);
		$this->_update('foresatt_mobil', $mobil);
	}
	
	private function _update( $key, $value ) {
		$this->updates[ $key ] = $value;
	}
	
	public function persist() {
		if( !is_array( $this->updates ) ) {
			throw new Exception('Kan ikke lagre ingen endringer til databasen. Oppdater objektet først');
		}
		$sql = new SQLins('samtykke_deltaker', ['id' => $this->getId()]);
		foreach( $this->updates as $key => $value ) {
			$sql->add( $key, $value );
		}
		$sql->run();
		$this->updates = [];
	}


	private function _populate( $db_row ) {
		$this->id = $db_row['id'];
		$this->year = $db_row['year'];
		$this->kategori = new PersonKategori( $this->person );
		$this->p_id = $db_row['p_id'];
		$this->mobil = $db_row['mobil'];
		$this->status = new Status( 
			$db_row['status'], 
			$db_row['status_timestamp'], 
			$db_row['status_ip']
		);
		$this->foresatt = new Foresatt( 
			$db_row['foresatt_navn'],
			$db_row['foresatt_mobil'],
			$db_row['foresatt_status'], 
			$db_row['foresatt_status_timestamp'],
			$db_row['foresatt_status_ip']
		);
		$this->last_change = $db_row['last_change'];
	}
	private function _createIfNotExists( $person, $year ) {
		$row = self::_load( $person, $year );
		
		if( !$row ) {
			$row = Person::create(
				$person,
				$year
			);
		}
		return $row;
	}
	
	private static function _load( $person, $year ) {
		$sql = new SQL("
			SELECT *
			FROM `samtykke_deltaker`
			WHERE `p_id` = '#p_id'
			AND `year` = '#season'",
			[
				'p_id' => $person->getId(),
				'season' => $year,
			]
		);
		return $sql->run('array');
	}
	
	public static function create( $person, $year ) {
		$kategori = new PersonKategori( $person );
		
		$sql = new SQLins('samtykke_deltaker');
		$sql->add('year', $year);
		$sql->add('kategori', $kategori->getId() );
		$sql->add('p_id', $person->getId() );
		$sql->add('mobil', $person->getMobil() );
#		$sql->add('status', 'ikke_sendt');
		
        try {	
            $sql->run();
            $rad_id = $sql->insid();
        } catch( Exception $e ) {
        }		
		return self::_load( $person, $year );
	}
}
