<?php

require_once('UKM/sql.class.php');
require_once('UKM/tid.class.php');

class tittel {

	var $t_id = false;

	public function videresend($videresendFra, $videresendTil) {
		if((int)$videresendFra==0 || (int)$videresendTil==0)
			return false;
		$test_fylkestep = new SQL("SELECT * FROM `smartukm_fylkestep`
								  WHERE `pl_id` = '#plid'
								  AND `pl_from` = '#pl_from'
								  AND `b_id` = '#bid'
								  AND `t_id` = '#t_id'",
								  array('plid'=>$videresendTil, 
								  		'bid'=>$this->b_id,
										'pl_from'=>$videresendFra,
										't_id'=>$this->t_id));
		$test_fylkestep = $test_fylkestep->run();

		if (mysql_num_rows($test_fylkestep)==0) {
			$videresend_innslag = new SQLins('smartukm_fylkestep');
			$videresend_innslag->add('pl_id', $videresendTil);
			$videresend_innslag->add('pl_from', $videresendFra);
			$videresend_innslag->add('b_id', $this->b_id);
			$videresend_innslag->add('t_id', $this->t_id);
			$videresend_innslag->run();
		}
		return true;
	} 
	
	public function avmeld($videresendFra, $videresendTil) {
		$slett_relasjon = new SQLdel('smartukm_fylkestep',
			array('pl_id'=>$videresendTil,
				  'pl_from'=>$videresendFra,
				  'b_id'=>$this->b_id,
				  't_id'=>$this->t_id));
		$slett_relasjon->run();
		return true;
	}

	public function update($field, $post_key=false) {
		if(!$post_key)
			$post_key = $field;
		if($_POST[$post_key] == $_POST['log_current_value_'.$post_key])
			return true;
				
		$qry = new SQLins($this->form, array('t_id'=>$this->t_id));
		$qry->add($this->tableFields[$this->form][$field], $_POST[$post_key]);
//		echo $qry->debug();
		$qry->run();
	}
	
	public function create($b_id) {
		$qry = new SQLins($this->form);
		$qry->add('b_id', $b_id);
//		var_dump($qry->debug());
		$qry->run();
		$this->t_id = $qry->insid();
		
		$this->__construct($this->t_id, $this->form);
	}
	
	public function delete() {
		$qry = new SQLdel($this->form, array('t_id'=>$this->t_id));
		return $qry->run();
	}
	
	function __construct($t_id,$form) {
		$this->form = $form;
		
		if ($t_id === false)
			return;
		
		$this->tableFields['smartukm_titles_scene']['tittel'] = 't_name';
		$this->tableFields['smartukm_titles_scene']['tekst_av'] = 't_titleby';
		$this->tableFields['smartukm_titles_scene']['melodi_av'] = 't_musicby';
		$this->tableFields['smartukm_titles_scene']['koreografi'] = 't_coreography';
		$this->tableFields['smartukm_titles_scene']['varighet'] = 't_time';
		$this->tableFields['smartukm_titles_scene']['season'] = 'season';
		$this->tableFields['smartukm_titles_scene']['instrumental'] = 't_instrumental';
		$this->tableFields['smartukm_titles_scene']['selvlaget'] = 't_selfmade';
		$this->tableFields['smartukm_titles_scene']['litterature_read'] = 't_litterature_read';
		
		$this->tableFields['smartukm_titles_exhibition']['tittel'] = 't_e_title';
		$this->tableFields['smartukm_titles_exhibition']['type'] = 't_e_type';
		$this->tableFields['smartukm_titles_exhibition']['beskrivelse'] = 't_e_comments';
		$this->tableFields['smartukm_titles_exhibition']['teknikk'] = 't_e_technique';
		$this->tableFields['smartukm_titles_exhibition']['season'] = 'season';
		
		$this->tableFields['smartukm_titles_video']['tittel'] = 't_v_title';
		$this->tableFields['smartukm_titles_video']['varighet'] = 't_v_time';	
		$this->tableFields['smartukm_titles_video']['format'] = 't_v_format';		
		$this->tableFields['smartukm_titles_video']['beskrivelse'] = 't_v_comments';
		$this->tableFields['smartukm_titles_video']['season'] = 'season';

		$this->tableFields['smartukm_titles_other']['tittel'] = 't_o_function';
		$this->tableFields['smartukm_titles_other']['erfaring'] = 't_o_experience';
		$this->tableFields['smartukm_titles_other']['kommentar'] = 't_o_comments';

		
		$this->t_id = $t_id;
		
		$qry = new SQL("SELECT * FROM `#form` WHERE `t_id` = '#bid'",
					array('form'=>$form,'bid'=>$t_id));
		$r = $qry->run('array');

		switch($form) {
			case 'smartukm_titles_scene':
				$this->_scene($r);
				break;
			case 'smartukm_titles_exhibition':
				$this->_utstilling($r);
				break;
			case 'smartukm_titles_video':
				$this->_film($r);
				break;
			case 'smartukm_titles_other':
				$this->_annet($r);
				break;
		}
		$this->b_id = $r['b_id'];
		if(!empty($this->varighet))
			$this->tid = $this->_secondtominutes($this->varighet);
		if($this->parentes == '()')
			$this->parentes = '';
		$this->_detaljer();
	}
	
	public function videresendt( $pl_to_id ) {
		$videresendt = new SQL("SELECT *
					FROM `smartukm_fylkestep`
					WHERE `pl_id` = '#pl_id'
					AND `b_id` = '#b_id'
					AND `t_id` = '#t_id'",
					array('pl_id'=>$pl_to_id,
					      'b_id'=>$this->b_id,
					      't_id'=>$this->t_id)
				       );
		$videresendt = $videresendt->run();
		return !mysql_num_rows($videresendt) == 0;
	}
	
	private function _detaljer(){
		$this->detaljer = substr($this->parentes, 1, strlen($this->parentes)-2);
	}
	
	public function g($key) {
		return $this->get($key);
	}

	public function get($key) {
		if(is_array($this->$key))
			return $this->$key;
			
		return $this->$key;	
	}

	public function set( $key, $value ) {
		$this->$key = $value;
		$this->lagre[$key] = $value;
	}
	
	public function lagre() {
		$qry = new SQLins($this->form, array('t_id' => $this->t_id ) );
		$count = 0;
	
		if( is_array( $this->lagre ) ) {
			foreach( $this->lagre as $key => $value ) {
				$qry->add( $this->tableFields[$this->form][ $key ], $value );	
				$count++;
			}
		}
		if ($count > 0) {
			$qry->run();
		}
		$this->lagre = array();
		
		$qry->run();
	}

	
	private function _scene($r) {
		$this->tittel = utf8_encode(stripslashes($r['t_name']));
		$this->tekst_av = utf8_encode($r['t_titleby']);
		if($this->tekst_av=='instrumental')
			$this->tekst_av = '';
		$this->melodi_av = utf8_encode($r['t_musicby']);
		$this->koreografi = utf8_encode($r['t_coreography']);
		$this->varighet = (int) $r['t_time'];
		$this->selvlaget = $r['t_selfmade'];
		$this->instrumental = $r['t_instrumental'];
		$this->litterature_read = $r['t_litterature_read'];
		
		$this->parentes = '(';
		if($this->melodi_av == $this->tekst_av && !empty($this->melodi_av))
			$this->parentes .= 'Tekst og melodi: '.$this->tekst_av.'';
		else{
			if(!empty($this->tekst_av))
				$this->parentes .= 'Tekst: '. $this->tekst_av;
			if(!empty($this->melodi_av))
				$this->parentes .= (!empty($this->tekst_av) ? ' - ':''). 'Melodi: '. $this->melodi_av;
		}
		
		if (!empty($this->koreografi)) {
			$this->parentes .= 'Koreografi: '.$this->koreografi;
		}
		$this->parentes .= ')';		
	}
	
	private function _utstilling($r) {
		$this->tittel = utf8_encode(stripslashes($r['t_e_title']));
		$this->type = utf8_encode($r['t_e_type']);
		$this->teknikk = utf8_encode($r['t_e_technique']);
		$this->format = utf8_encode($r['t_e_format']);
		$this->beskrivelse = utf8_encode($r['t_e_comments']);
		$this->varighet = 0;

		$this->parentes = '(';

			if(!empty($this->type))
				# fjernet utf8_encode fordi det gjøres over
				$this->parentes .= 'Type: '. $this->type;
			if(!empty($this->tekst_av))
				# fjernet utf8_encode fordi det gjøres over
				$this->parentes .= 'Teknikk: '. $this->teknikk;
		$this->parentes .= ')';
	}
	
	private function _film($r) {
		$this->tittel = utf8_encode(stripslashes($r['t_v_title']));
		$this->format = utf8_encode($r['t_v_format']);
		$this->varighet = (int) $r['t_v_time'];
		$this->parentes = '('.utf8_encode($r['t_v_format']).')';
	}
	
	private function _annet($r) {
		$this->tittel = utf8_encode(stripslashes($r['t_o_function']));
		$this->erfaring = utf8_encode($r['t_o_experience']);
		$this->kommentar = utf8_encode($r['t_o_comments']);
		$this->varighet = 0;
		$this->parentes = '('.utf8_encode($r['t_o_comments']).')';
	}

	private function _secondtominutes($sec) {
		$q = floor($sec / 60);
		$r = $sec % 60;
		
		if ($q == 0)
			return $r.'s';
			
		if ($r == 0)
			return $q.' min';
		
		return $q.'m '.$r.'s';
	}

}


class tittel_v2 {
	var $table = null;
	var $id = null;

	var $tittel = null;
	var $tekst_av = null;
	var $melodi_av = null;
	var $koreografi_av = null;
	var $varighet = null;
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
		if(!in_array($this->getTable(), array('smartukm_titles_scene', 'smartukm_titles_video'))) {
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

	
	
	private function getParentes() {
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

}
?>
