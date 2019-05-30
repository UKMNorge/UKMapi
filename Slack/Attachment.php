<?php
	
namespace UKMNorge\Slack;

class Attachment {
	var $id = null;
	var $callback_id = null;
	var $fields = null;
	var $actions = null;
	var $text = null;
	var $fallback = null;
	var $color = null;
	var $type = null;
	
	public function __construct( $id, $callback_id ) {
		$this->setId( $id );
		$this->setCallbackId( $callback_id );
		$this->fields = [];
		$this->actions = [];
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

	
	public function setCallbackId( $callback_id ) {
		$this->callback_id = $callback_id;
		return $this;
	}
	public function getCallbackId() {
		return $this->callback_id;
	}
	
	public function addField( $field ) {
		$this->fields[ $field->getId() ] = $field;
		return $this;
	}
	public function getFields() {
		return $this->fields;
	}
	public function hasFields() {
		return sizeof( $this->getFields() ) > 0;
	}
	
	public function addAction( $action ) {
		$this->actions[ $action->getId() ] = $action;
		return $this;
	}
	public function getActions() {
		return $this->actions;
	}
	
	public function hasActions() {
		return sizeof( $this->getActions() ) > 0;
	}
	
	public function setFallback( $fallback ) {
		$this->fallback = $fallback;
		return $this;
	}
	public function getFallback() {
		return $this->fallback;
	}
	public function setColor( $color ) {
		$this->color = $color;
		return $this;
	}
	public function getColor() {
		return $this->color;
	}
	
	public function setType( $type ) {
		$this->type = $type;
		return $this;
	}
	public function getType() {
		return $this->type;
	}
	
	

}