<?php
	
namespace UKMNorge\Slack;

class Action {
	var $id = null;
	var $name = null;
	var $optionGroups = null;
	var $options = null;
	var $type = null;
	var $data_source = null;
	var $text = null;
	
	public function __construct( $id, $name ) {
		$this->setId( $id );
		$this->setName( $id );
		$this->setText( $name );
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
	
	public function setType( $type ) {
		$this->type = $type;
		return $this;
	}
	public function getType() {
		return $this->type;
	}
	
	public function setDataSource( $source ) {
		$this->data_source = $source;
		return $this;
	}
	public function getDataSource() {
		return $this->data_source;
	}
	
	public function addOptionGroup( $optionGroup ) {
		$this->optionGroups[ $optionGroup->getId() ] = $optionGroup;
		return $this;
	}
	
	public function getOptionGroups() {
		return $this->optionGroups;
	}
	public function hasOptionGroups() {
		return sizeof( $this->getOptionGroups() ?? [] ) > 0;
	}
	
	
	public function addOption( $option ) {
		$this->options[ $option->getId() ] = $option;
		return $this;
	}
	public function getOptions() {
		return $this->options;
	}
	public function hasOptions() {
		return sizeof( $this->getOptions() ?? [] ) > 0;
	}
	
	public function setText( $text ) {
		$this->text = $text;
		return $this;
	}
	public function getText() {
		return $this->text;
	}
}
