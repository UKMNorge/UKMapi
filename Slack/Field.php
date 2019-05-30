<?php

namespace UKMNorge\Slack;
	
class Field {
	var $id = null;
	var $title = null;
	var $value = null;
	var $short = true;
	
	public function __construct( $id, $title, $value, $short=true) {
		$this->setId( $id );
		$this->setTitle( $title );
		$this->setValue( $value );
		$this->setShort( $short );
	}
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
	
	public function setTitle( $title ) {
		$this->title = $title;
		return $this;
	}
	public function getTitle() {
		return $this->title;
	}
	
	public function setValue( $value ) {
		$this->value = $value;
		return $this;
	}
	public function getValue() {
		return $this->value;
	}
	
	public function setShort( $short ) {
		$this->short = $short;
		return $this;
	}
	public function getShort() {
		return $this->short;
	}
}
