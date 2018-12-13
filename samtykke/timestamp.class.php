<?php
class samtykke_timestamp {
	var $timestamp = null;

	public function __construct( $timestamp ) {
		if( is_string( $timestamp ) ) {
			$this->timestamp = DateTime::createFromFormat('Y-m-d H:i:s', $timestamp);
		} else {
			$this->timestamp = $timestamp;
		}
	}
	
	public function getForDatabase() {
		if( !is_object( $this->timestamp ) ) {
			return null;
		}
		return $this->timestamp->format('Y-m-d H:i:s');
	}
	
	public function __toString() {
		if( null == $this->timestamp ) {
			return 'ikke tilgjengelig';
		}
		return $this->timestamp->format('d.m.Y H:i:s');
	}
}