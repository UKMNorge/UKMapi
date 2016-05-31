<?php
class fylke {
	var $id = null;
	var $link = null;
	var $name = null;
	
	
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
}