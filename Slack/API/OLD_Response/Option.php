<?php

namespace UKMNorge\Slack;
	
class Option {
	var $id = null;
	var $text = null;
	var $value = null;
	
	public function __construct( $id, $text, $value) {
		$this->setId( $id );
		$this->setText( $text );
		$this->setValue( $value );
	}
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
	
	public function setText( $text ) {
		$this->text = $text;
		return $this;
	}
	public function getText() {
		return $this->text;
	}
	
	public function setValue( $value ) {
		$this->value = $value;
		return $this;
	}
	public function getValue() {
		return $this->value;
	}
}
