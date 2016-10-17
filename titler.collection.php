<?php
require_once('UKM/tid.class.php');
	
class titler {
	var $id = null;
	var $type = null;
	var $monstring = null;
	var $titler = null;
	var $table = null;
	var $table_field_title = null;
	var $varighet = 0;
		
	public function __construct( $b_id, $type, $monstring ) {
		$this->setId( $b_id );
		$this->setType( $type );
		$this->setMonstring( $monstring );
	}
	
	public function getAll() {
		if( null == $this->titler ) {
			$this->_load();
		}
		return $this->titler;
	}
	
	public function getAntall() {
		return sizeof( $this->getAll() );
	}
	
	/**
	 * get
	 * Finn en tittel med gitt ID
	 *
	 * @param integer id
	 * @return person
	**/
	public function get( $id ) {
		foreach( $this->getAll() as $tittel ) {
			if( $tittel->getId() == $id ) {
				return $tittel;
			}
		}
		throw new Exception('PERSONER_V2: Kunne ikke finne tittel '. $id .' i innslag '. $this->_getBID());
	}
	
	public function setVarighet( $seconds ) {
		$this->varighet = $seconds;
		return $this;
	}
	public function getVarighet() {
		$sek = 0;
		foreach( $this->getAll() as $tittel ) {
			$sek += $tittel->getVarighet()->getSekunder();
		}
		return new tid( $sek );
	}
	
	private function _load() {
		$this->titler = array();
		
		// Til og med 2013-sesongen brukte vi tabellen "landstep" for videresending til land
		if( 'land' == $this->getMonstring()->getType() && 2014 > $this->getMonstring()->getSesong() ) {
			$SQL = new SQL("SELECT `title`.*,
								   `videre`.`id` AS `videre_if_not_empty`
							FROM `#table` AS `title`
							LEFT JOIN `smartukm_landstep` AS `videre`
								ON(`videre`.`b_id` = `title`.`b_id` AND `videre`.`t_id` = `title`.`t_id`)
							WHERE `title`.`b_id` = '#b_id'
							GROUP BY `title`.`t_id`
							ORDER BY `title`.`#titlefield`",
						array('table' => $this->getTable(),
							  'titlefield' => $this->getTableFieldnameTitle(),
							  'b_id' => $this->getId()
							)
						);
		} else {
			$SQL = new SQL("SELECT `title`.*,
								GROUP_CONCAT(`videre`.`pl_id`) AS `pl_ids`
							FROM `#table` AS `title`
							LEFT JOIN `smartukm_fylkestep` AS `videre`
								ON(`videre`.`b_id` = `title`.`b_id` AND `videre`.`t_id` = `title`.`t_id`)
							WHERE `title`.`b_id` = '#b_id'
							GROUP BY `title`.`t_id`
							ORDER BY `title`.`#titlefield`",
						array('table' => $this->getTable(),
							  'titlefield' => $this->getTableFieldnameTitle(),
							  'b_id' => $this->getId()
							)
						);
			$res = $SQL->run();
		}
		
		if( $res ) {
			$varighet = 0;
			while( $row = mysql_fetch_assoc( $res ) ) {
				// Hvis innslaget er pre 2014 og på landsmønstring jukser vi
				// til at den har pl_ids for å få lik funksjonalitet videre
				if( isset( $row['videre_if_not_empty'] ) ) {
					if( is_numeric( $row['videre_if_not_empty'] ) ) {
						$row['pl_ids'] = $this->getMonstring()->getId();
					} else {
						$row['pl_ids'] = null;
					}
				}
				// Legg til tittel i array
				$tittel = new tittel_v2( $row, $this->getType()->getTabell() );
				$this->titler[] = $tittel;
				$varighet += $tittel->getVarighet();
			}
		}
		
		return $this->titler;
	}
	
	/**
	 * Fjerner tittelen helt fra en lokalmønstring.
	 * TODO: Fjerner tittelen fra videresending hvis dette er fylkes eller landsmønstring.
	 *
	 * @param int $id 
	 * @return $this
	 */
	public function fjern($tittel) {
		if( 'write_tittel' != get_class($tittel) ) {
			throw new Exception("TITLER: Fjerning av tittel krever skriverettigheter til tittelen!");
		}

		if( !is_numeric($tittel->getId()) ) {
			throw new Exception("TITLER: Fjerning av en tittel krever at tittelen har en numerisk ID!");
		}

		switch( $this->getMonstring()->getType() ) {
			case 'kommune':
				$this->_fjernLokalTittel($tittel);
				break;
			default:
				throw new Exception("TITLER: Fjerning av tittel er ikke implementert for denne mønstringstypen: ". $this->getMonstring()->getType() );
		}

		return $this;
	}

	private function _fjernLokalTittel($tittel) {
		switch($tittel->getTable()) {
			case 'smartukm_titles_scene':
				$qry = new SQLdel( "smartukm_titles_scene", array('t_id' => $tittel->getId()) );
				break;
			default:
				throw new Exception("TITLER: Fjerning av en tittel er kun implementert for scene-innslag");
		}
		
		$res = $qry->run();
		if($res == 1)
			return true;
		else 
			throw new Exception("TITLER: Klarte ikke fjerne tittel " . $tittel->getTittel());
			
	}

	/**
	 * Sett ID
	 *
	 * @param integer id 
	 *
	 * @return $this
	**/
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	/**
	 * Hent ID
	 * @return integer $id
	**/
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Sett type
	 * Hvilken kategori faller innslaget inn under?
	 *
	 * @param integer $type
	 * @param string $kategori
	 *
	 * @return $this;
	**/
	public function setType( $type ) {#, $kategori=false ) {
		#require_once('UKM/innslag_typer.class.php');
		$this->type = $type; 
		#$this->type = innslag_typer::getById( $type );
		
		// Sett hvilken tabell som skal brukes
		$this->setTable( $this->getType()->getTabell() );
		
		return $this;
	}
	/**
	 * Hent type
	 * Hvilken kategori innslaget faller inn under
	 *
	 * @return innslag_type $type
	**/
	public function getType( ) {
		return $this->type;
	}
	
	/**
	 * Sett mønstring
	 *
	 * @param $monstring
	 * @return $this
	**/
	public function setMonstring( $monstring ) {
		$this->monstring = $monstring;
		return $this;
	}
	/**
	 * Hent mønstring
	 *
	 * @return monstring
	**/
	public function getMonstring() {
		return $this->monstring;
	}
	
	/**
	 * Sett tabellnavn
	 *
	 * @param $table
	 * @return $this
	**/
	public function setTable( $table ) {
		$this->table = $table;
		
		// Sett navn på tittelfeltet
		switch( $table ) {
			case 'smartukm_titles_exhibition':
				$fieldname_title = 't_e_title';
				break;
			case 'smartukm_titles_other':
				$fieldname_title = 't_o_function';
				break;
			case 'smartukm_titles_scene':
				$fieldname_title = 't_name';
				break;
			case 'smartukm_titles_video':
				$fieldname_title = 't_v_title';
				break;
			default:
				throw new Exception('TITLER: Tittel-type ('.$type .') ikke støttet');
		}
		$this->setTableFieldnameTitle( $fieldname_title );

		return $this;
	}

	/**
	 * Hent tabellnavn
	 *
	 * @return string $tabellnavn
	**/
	public function getTable() {
		return $this->table;
	}
	
	/**
	 * Sett navn på tittelfelt
	 *
	 * @param $tittelfelt
	 * @return $this
	**/
	public function setTableFieldnameTitle( $tittelfelt ) {
		$this->table_field_title = $tittelfelt;
		return $this;
	}
	/**
	 * Hent navn på tittelfelt
	 *
	 * @return string $tittelfelt
	**/
	public function getTableFieldnameTitle() {
		return $this->table_field_title;
	}
}