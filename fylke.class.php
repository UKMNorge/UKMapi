<?php
class fylke {
	var $id = null;
	var $link = null;
	var $navn = null;
	
	public function __construct( $id, $link, $name ) {
		$this->setId( $id );
		$this->setLInk( $link );
		$this->setNavn( $name );
	}
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
	
	public function setLink( $link ) {
		$this->link = $link;
		return $this;
	}
	public function getLink() {
		return $this->link;
	}
	
	public function setNavn( $navn ) {
		$this->navn = $navn;
		return $this;
	}
	public function getNavn() {
		return $this->navn;
	}
	
	public function getKommuner() {
		if( null == $this->kommuner ) {
			require_once('UKM/kommuner.collection.php');
			require_once('UKM/kommune.class.php');

			$this->kommuner = new kommuner();

			$sql = new SQL("SELECT * 
							FROM `smartukm_kommune` 
							WHERE `idfylke` = '#fylke'",
						  array('fylke'=>$this->getId() )
						);
			$res = $sql->run();
			
			if( $res ) {
				while( $r = mysql_fetch_assoc( $res ) ) {
					$this->kommuner->add( new kommune( $r ) );
				}
			}
		}
		return $this->kommuner;
	}
	
	public function __toString() {
		return $this->getNavn();
	}
}