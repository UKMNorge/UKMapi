<?php
require_once('UKM/nominasjon_media.class.php');
require_once('UKM/logger.class.php');

class write_nominasjon_media extends nominasjon_media {
	var $changes = array();
	var $loaded = false;


	public function save() {
		$sql = new SQLins('ukm_nominasjon_media', ['nominasjon' => $this->getId() ] );
		$sql->add('pri_1', $this->getPri1() );
		$sql->add('pri_2', $this->getPri2() );
		$sql->add('pri_3', $this->getPri3() );
		$sql->add('annet', $this->getAnnet() );
		$sql->add('beskrivelse', $this->getBeskrivelse() );
		$sql->add('samarbeid', $this->getSamarbeid() );
		$sql->add('erfaring', $this->getErfaring() );
		$res = $sql->run();
	}


	private function _setLoaded() {
		$this->loaded = true;
		$this->_resetChanges();
		return $this;
	}
	private function _loaded() {
		return $this->loaded;
	}
	
	public function getChanges() {
		return $this->changes;
	}
	
	private function _resetChanges() {
		$this->changes = [];
	}
	
	private function _change( $tabell, $felt, $action, $value ) {
		$data = array(	'tabell'	=> $tabell,
						'felt'		=> $felt,
						'action'	=> $action,
						'value'		=> $value
					);
		$this->changes[ $tabell .'|'. $felt ] = $data;
	}
}