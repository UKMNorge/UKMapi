<?php 
require_once('UKM/_collection.class.php');
	
class kommuner extends Collection {
	
	public function getIdArray() {
		$array = array();
		foreach( $this as $kommune ) {
			$array[] = $kommune->getId();
		}
		return $array;
	}
	
	public function getKeyValArray() {
		$array = array();
		foreach( $this as $kommune ) {
			$array[ $kommune->getId() ] = $kommune->getNavn();
		}
		return $array;
	}
	
}