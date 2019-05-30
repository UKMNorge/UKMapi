<?php

namespace UKMNorge\Slack;
	
class OptionGroup {
	var $id = null;
	var $options = null;
	var $name = null;
	
	public function __construct( $id, $name ) {
		$this->setId( $id );
		$this->setName( $name );
		$this->options = [];
	}
	
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	public function getId() {
		return $this->id;
	}
	
	public function setName( $name ) {
		$this->name = $name;
		return $this;
	}
	public function getName() {
		return $this->name;
	}
	
	public function addOption( $option ) {
		$this->options[ $option->getId() ] = $option;
	}
	public function getOptions() {
		return $this->options;
	}
}