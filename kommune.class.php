<?php
require_once('UKM/sql.class.php');
require_once('UKM/fylker.class.php');
	
class kommune {
	public function __construct( $kid_or_row ) {
		if( is_numeric( $kid_or_row ) ) {
			$this->_loadByID( $kid_or_row );
		} else {
			$this->_loadByRow( $kid_or_row );
		}
	}
	private function _loadByID( $id ) {
		$sql = new SQL("SELECT *
						FROM `smartukm_kommune`
						WHERE `id` = '#id'",
						array('id' => $id ) );
		$res = $sql->run('array');
		if( !is_array( $res ) ) {
			$this->id = false;
		} else {
			$this->_loadByRow( $res );
		}
	}
	private function _loadByRow( $res ) {
		$this->id = $res['id'];
		$this->name = utf8_encode($res['name']);
		$this->fylke = fylker::getById( $res['idfylke'] );
		$this->name_nonutf8 = $res['name'];
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function getNavn() {
		if( null == $this->name ) {
			return 'ukjent';
		}
		return $this->name;
	}
	
	public function getNavnUtenUTF8() {
		return $this->name_nonutf8;
	}
	
	public function getFylke() {
		return $this->fylke;
	}
	public function __toString() {
		return $this->getNavn();
	}
	
	public function getURLsafe() {
		$text = mb_strtolower( $this->getNavn() );
		$text = htmlentities($text);
	
		// eh, noen rare her, men muligens pga tidl dobbeltencode utf8
		$ut = array('&aring;','&aelig;','&oslash;','&atilde;','&ocedil;','&uuml;');
		$inn= array('a','a','o','o','o','u');
		$text = str_replace($ut, $inn, $text);
		
		$text = preg_replace("/[^A-Za-z0-9-]/","",$text);
		return $text;
	}
}