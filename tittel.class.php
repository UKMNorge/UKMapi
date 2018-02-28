<?php

require_once('UKM/sql.class.php');
require_once('UKM/tid.class.php');
require_once('UKM/v1_tittel.class.php');

class tittel_v2 {
	var $context = null;
	
	var $table = null;
	var $id = null;
	var $videresendtTil = null;

	var $tittel = null;
	var $tekst_av = null;
	var $melodi_av = null;
	var $koreografi_av = null;
	var $varighet = null;
	var $sekunder = null;
	var $selvlaget = null;
	var $litteratur_read = null;
	var $instrumental = null;
	
	var $type;
	var $teknikk = null;
	var $format = null;
	var $beskrivelse = null;
	
	var $erfaring = null;
	var $kommentar = null;
	

	public function __construct( $id_or_row, $table ) {
		if(null == $table) {
			throw new Exception('TITTEL_V2: Kan ikke initiere uten tabell.');
		}
		if ( null == $id_or_row ) {
			throw new Exception('TITTEL_V2: Kan ikke laste tittel uten id eller data.');
		}

		$this->setTable( $table );
		if( is_numeric( $id_or_row ) ) {
			$this->_load_from_id( $id_or_row );
		} else {
			$this->_load_from_dbrow( $id_or_row );
		}
		
	}
	
	private function _load_from_id( $id ) {
		if(!in_array($this->getTable(), array('smartukm_titles_scene', 'smartukm_titles_video', 'smartukm_titles_exhibition'))) {
			throw new Exception('TITTEL_V2: Load from DB not supported yet for this type: '.$this->getTable());
		}

		$qry = new SQL("SELECT * FROM ".$this->getTable()." WHERE `t_id` = '#id'", array('id' => $id));
		
		$row = $qry->run('array');
		$this->_load_from_dbrow( $row );
	}
	
	private function _load_from_dbrow( $row ) {
		$this->setId( $row['t_id'] );
		
		switch( $this->getTable() ) {
			case 'smartukm_titles_exhibition':
				$this->_load_utstilling( $row );
				break;
			case 'smartukm_titles_other':
				$this->_load_annet( $row );
				break;
			case 'smartukm_titles_scene':
				$this->_load_scene( $row );
				break;
			case 'smartukm_titles_video':
				$this->_load_film( $row );
				break;
			default:
				throw new Exception('TITTEL_V2: '. $this->getTable() .' not supported table type');
		}

		if( array_key_exists('pl_ids', $row ) ) {
			$this->setVideresendtTil( explode(',', $row['pl_ids']) );
		}

	}
	
	/**
	 * Er videresendt
	 * Er personen videresendt til gitt mønstring?
	 *
	 * @param int $pl_id
	 * @return bool
	**/
	public function erVideresendt( $pl_id ) {
		if( is_object( $pl_id ) && ( 'write_monstring' == get_class( $pl_id ) || 'monstring' == get_class( $pl_id ) ) ) {
			$pl_id = $pl_id->getId();
		}
		if( null == $this->videresendtTil ){
			throw new Exception( 'TITTEL_V2 (t'. $this->getId() .'): Kan ikke svare om tittel er videresendt '
								.'på objekt som ikke er initiert med pl_ids (via collection)');
		}
		return in_array($pl_id, $this->getVideresendtTil() );
	}
	
	/**
	 * Sett tittel
	 *
	 * @param string $tittel
	 * @return $this
	**/
	public function setTittel( $tittel ) {
		$this->tittel = $tittel;
		return $this;
	}
	/**
	 * Hent tittel
	 *
	 * @return string $tittel
	 *
	**/
	public function getTittel() {
		return $this->tittel;
	}
	
	/**
	 * Sett tekst av
	 *
	 * @param $tekst_av
	 * @return $this
	**/
	public function setTekstAv( $tekst_av ) {
		$this->tekst_av = $tekst_av;
		return $this;
	}
	/**
	 * Hent tekst av
	 *
	 * @return string $tekst_av
	 *
	**/
	public function getTekstAv() {
		return $this->tekst_av;
	}
	
	/** 
	 * Sett melodi av
	 * 
	 * @param string $melodi_av
	 * @return $melodi_av
	**/
	public function setMelodiAv( $melodi_av ) {
		$this->melodi_av = $melodi_av;
		return $this;
	}
	/**
	 * Hent melodi av
	 *
	 * @return string $melodi_av
	**/
	public function getMelodiAv() {
		return $this->melodi_av;
	}
	
	/**
	 * Sett koreografi av
	 *
	 * @param string $koreografi_av
	 * @return $this
	**/
	public function setKoreografiAv( $koreografi_av ) {
		$this->koreografi_av = $koreografi_av;
		return $this;
	}
	/**
	 * Hent koreografi av
	 *
	 * @return string $koreografi_av
	 *
	**/
	public function getKoreografiAv() {
		return $this->koreografi_av;
	}
	
	/**
	 * Sett varighet
	 *
	 * @param int $sekunder
	 * @return $this
	**/
	public function setVarighet( $sekunder ) {
		$this->sekunder = $sekunder;
		$this->varighet = new tid( $sekunder );
		return $this;
	}
	/**
	 * Hent varighet
	 *
	 * @return object tid
	 *
	**/
	public function getVarighet() {
		return $this->varighet;
	}
	
	/**
	 * Hent varigheten, men som sekunder
	 *
	 * @return int tid
	**/
	public function getVarighetSomSekunder() {
		return $this->sekunder;
	}
	
	/**
	 * Sett selvalget
	 *
	 * @param bool selvlaget
	 * @return $this
	**/
	public function setSelvlaget( $selvlaget ) {
		if( !is_bool( $selvlaget ) ) {
			throw new Exception('TITTEL_V2: Selvlaget må angis som boolean');
		}
		$this->selvlaget = $selvlaget;
		return $this;
	}
	/**
	 * Hent selvlaget
	 *
	 * @return bool selvlaget
	**/
	public function erSelvlaget() {
		return $this->selvlaget;
	}
	public function getSelvlaget() {
		return $this->erSelvlaget();
	}

	/**
	 * Sett skal litteratur leses opp?
	 *
	 * @param bool litteratur_read
	 * @return $this
	**/
	public function setLesOpp( $lesopp ) {
		if( !is_bool( $lesopp ) ) {
			throw new Exception('TITTEL_V2: Skal leses opp må angis som boolean');
		}
		$this->litteratur_read = $lesopp;
		return $this;
	}
	/**
	 * Skal litteratur leses opp?
	 *
	 * @return bool selvlaget
	**/
	public function erLesOpp() {
		return $this->litteratur_read;
	}
	/**
	 * ALIAS Skal litteratur leses opp?
	 *
	 * @return bool selvlaget
	**/
	public function skalLesesOpp() {
		return $this->erLesOpp();
	}
	public function getLesOpp() {
		return $this->erLesOpp();
	}
		
	/**
	 * Sett instrumental
	 *
	 * @param bool instrumental
	 * @return $this
	**/
	public function setInstrumental( $instrumental ) {
		if( !is_bool( $instrumental ) ) {
			throw new Exception('TITTEL_V2: Instrumental må angis som boolean');
		}
		$this->instrumental = $instrumental;
		return $this;
	}
	/**
	 * Er instrumental?
	 *
	 * @return bool $instrumental
	**/
	public function erInstrumental() {
		return $this->instrumental;
	}
	public function getInstrumental() {
		return $this->erInstrumental();
	}
	
	/**
	 * Sett type (for bl.a. utstilling)
	 *
	 * @param string $type
	 *
	 * @return $this;
	**/
	public function setType( $type ) {
		$this->type = $type;		
		return $this;
	}
	/**
	 * Hent type
	 *
	 * @return innslag_type $type
	**/
	public function getType( ) {
		return $this->type;
	}
	
	/**
	 * Sett beskrivelse (av kunstverk)
	 *
	 * @param beskrivelse
	 * @return $this
	**/
	public function setBeskrivelse( $beskrivelse ) {
		$this->beskrivelse = $beskrivelse;
		return $this;
	}
	/**
	 * Hent beskrivelse
	 *
	 * @return string $beskrivelse
	**/
	public function getBeskrivelse() {
		return $this->beskrivelse;
	}
	
	/**
	 * Sett teknikk (av kunstverk)
	 *
	 * @param teknikk
	 * @return $this
	**/
	public function setTeknikk( $teknikk ) {
		$this->teknikk = $teknikk;
		return $this;
	}
	/**
	 * Hent teknikk
	 *
	 * @return string $teknikk
	**/
	public function getTeknikk() {
		return $this->teknikk;
	}
	
	/**
	 * Sett format (av kunstverk)
	 *
	 * @param format
	 * @return $this
	**/
	public function setFormat( $format ) {
		$this->format = $format;
		return $this;
	}
	/**
	 * Hent format
	 *
	 * @return string $format
	**/
	public function getFormat() {
		return $this->format;
	}
	
	/**
	 * Sett erfaring
	 *
	 * @param string $erfaring
	 * @return $this
	**/
	public function setErfaring( $erfaring ) {
		$this->erfaring = $erfaring;
		return $this;
	}
	/**
	 * Hent erfaring
	 *
	 * @return string $erfaring
	 *
	**/
	public function getErfaring() {
		return $this->erfaring;
	}
	
	
	/**
	 * Sett kommentar
	 *
	 * @param string $kommentar
	 * @return $this
	**/
	public function setKommentar( $kommentar ) {
		$this->kommentar = $kommentar;
		return $this;
	}
	/**
	 * Hent kommentar
	 *
	 * @return string $kommentar
	 *
	**/
	public function getKommentar() {
		return $this->kommentar;
	}

	
	/**
	 * Populer objekt for scene-tittel
	 * 
	 * @param databaserad
	 *
	**/
	private function _load_scene( $row ) {
		$this->setTittel( utf8_encode( stripslashes($row['t_name']) ) );
		$this->setTekstAv( utf8_encode( $row['t_titleby'] ) );
		$this->setMelodiAv( utf8_encode( $row['t_musicby'] ) );
		$this->setKoreografiAv( utf8_encode($row['t_coreography'] ) );
		
		$this->setVarighet( (int) $row['t_time'] );
		
		$this->setSelvlaget( 1 == $row['t_selfmade'] );
		$this->setLitteraturLesOpp( 1 == $row['t_litterature_read'] );
		$this->setInstrumental( 1 == $row['t_instrumental'] );

		if( $this->erInstrumental() ) {
			$this->setTekstAv('');
		}
	}
	
	/**
	 * Sett om litteratur-innslaget skal leses opp
	 *
	 * @param bool
	*/
	public function setLitteraturLesOpp( $lesopp ) {
		if( !is_bool( $lesopp ) ) {
			throw new Exception('TITTEL_V2: Litteratur leses opp må angis som boolean');
		}
		$this->litteratur_read = $lesopp;
		return $this;
	}
	
	public function getLitteraturLesOpp() {
		return $this->litteratur_read;
	}
	
	/**
	 * Populer objekt for utstilling-tittel
	 *
	 * @param databaserad
	**/	
	private function _load_utstilling( $row ) {
		$this->setTittel( utf8_encode( stripslashes( $row['t_e_title'] ) ) );
		$this->setType( utf8_encode( $row['t_e_type'] ) );
		$this->setTeknikk( utf8_encode( $row['t_e_technique'] ));
		$this->setFormat( utf8_encode( $row['t_e_format'] ) );
		$this->setBeskrivelse( utf8_encode( $row['t_e_comments'] ));
		$this->setVarighet( 0 );
	}
	
	/**
	 * Populer objekt for film-tittel
	 *
	 * @param databaserad
	**/
	private function _load_film( $row ) {
		$this->setTittel( utf8_encode( stripslashes( $row['t_v_title'] ) ) );
		$this->setFormat( utf8_encode( $row['t_v_format'] ) );
		$this->setVarighet( (int) $row['t_v_time'] );
	}
	
	/**
	 * Populer objekt for andre-titler
	 *
	 * @param databaserad
	**/
	private function _load_annet( $row ) {
		$this->setTittel( utf8_encode( stripslashes( $row['t_o_function'] ) ) );
		$this->setErfaring( utf8_encode( $row['t_o_experience'] ) );
		$this->setKommentar( utf8_encode( $row['t_o_comments'] ) );
		$this->setVarighet( 0 );
	}

	
	
	public function getParentes() {
		$tekst = '';
		switch( $this->getTable() ) {
			case 'smartukm_titles_video':
				return utf8_encode($r['t_v_format']);
				
			case 'smartukm_titles_other':
				return utf8_encode($r['t_v_format']);

			case 'smartukm_titles_scene':
				if( $this->getTekstAv() == $this->getMelodiAv() ) {
					return 'Tekst og melodi: '. $this->getTekstAv();
				}
				
				$tekst = '';
				if( !empty( $this->getTekstAv() ) ) {
					$tekst .= 'Tekst: '. $this->getTekstAv() .' ';
				}
				if( !empty( $this->getMelodiAv() ) ) {
					$tekst .= 'Melodi: '. $this->getMelodiAv() .' ';
				}
				if( !empty( $this->getKoreografiAv() ) ) {
					$tekst .= 'Koreografi: '. $this->getKoreografiAv();
				}
			break;
			case 'smartukm_titles_exhibition':
				$tekst = '';
				
				if( !empty( $this->getType() ) ) {
					$tekst .= 'Type: '. $this->getType() .' ';
				}
				if( !empty( $this->getTeknikk() ) ) {
					$tekst .= 'Teknikk: '. $this->getTeknikk() .' ';
				}
			break;
		}
		return rtrim( $tekst );
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
	 * Sett tabellnavn
	 *
	 * @param $table
	 * @return $this
	**/
	public function setTable( $table ) {
		$this->table = $table;
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
	 * Sett videresendt til
	 *
	 * @param array pl_ids
	 * @return $this
	**/
	public function setVideresendtTil( $videresendtTil ) {
		$this->videresendtTil = $videresendtTil;
		return $this;
	}
	/**
	 * Hent videresendt til
	 * 
	 * @return array $videresendtTil
	**/
	public function getVideresendtTil() {
		return $this->videresendtTil;
	}

	
	public function setContext( $context ) {
		$this->context = $context;
		return $this;
	}
	public function getContext() {
		return $this->context;
	}

}
?>
