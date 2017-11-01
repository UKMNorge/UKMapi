<?php
class fylke {
	var $id = null;
	var $link = null;
	var $navn = null;
	var $attributes = null;
	var $kommuner = null;
	
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
	
	/**
	 * Sett attributt
	 * Sett egenskaper som for enkelhets skyld kan følge mønstringen et lite stykke
	 * Vil aldri kunne lagres
	 *
	 * @param string $key
	 * @param $value
	 *
	 * @return innslag
	**/
	public function setAttr( $key, $value ) {
		$this->attributes[ $key ] = $value;
		return $this;
	}
	
	/**
	 * Hent attributt
	 *
	 * @param string $key
	 *
	 * @return value
	**/
	public function getAttr( $key ) {
		return isset( $this->attributes[ $key ] ) ? $this->attributes[ $key ] : false;
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
	
	/**
	 * getKommunerUtenGjester
	 * fjerner gjestekommunen fra kommune-lista og returnerer forøvrig getKommuner
	 *
	 * @return array kommuner
	**/
	public function getKommunerUtenGjester() {
		$kommuner = [];
		foreach( $this->getKommuner() as $kommune ) {
			if( $kommune->getId() != ($this->getId().'90') ) {
				$kommuner[] = $kommune;
			}
		}
		return $kommuner;
	}

	
	public function __toString() {
		return $this->getNavn();
	}
	
	/**
	 * getURLsafe
	 * Alias av getLink for consistency kommune.class
	 * @return string link
	**/
	public function getURLsafe() {
		return $this->getLink();
	}
}