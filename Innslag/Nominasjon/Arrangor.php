<?php

namespace UKMNorge\Innslag\Nominasjon;

class Arrangor extends Nominasjon {
	
	var $type_lydtekniker = false;
	var $type_lystekniker = false;
	var $type_vertskap = false;
	var $type_produsent = false;
	
	var $samarbeid;
	var $erfaring;
	var $suksesskriterie;
	var $annet;
	
	var $lyderfaring1;
	var $lyderfaring2;
	var $lyderfaring3;
	var $lyderfaring4;
	var $lyderfaring5;
	var $lyderfaring6;
	
	var $lyserfaring1;
	var $lyserfaring2;
	var $lyserfaring3;
	var $lyserfaring4;
	var $lyserfaring5;
	var $lyserfaring6;
	
	var $voksen_annet;
	var $voksen_efaring;
	var $voksen_samarbeid;
	
	var $sorry;
	
	public function _loadByRow( $row ) {
		parent::_loadByRow( $row );
		
		$this->setLydtekniker( $row['type_lydtekniker'] == 'true' );
		$this->setLystekniker( $row['type_lystekniker'] == 'true' );
		$this->setVertskap( $row['type_vertskap'] == 'true' );
		$this->setProdusent( $row['type_produsent'] == 'true' );

		$this->setSamarbeid( $row['samarbeid'] );
		$this->setErfaring( $row['erfaring'] );
		$this->setSuksesskriterie( $row['suksesskriterie'] );
		$this->setAnnet( $row['annet'] );

		$this->setVoksenSamarbeid( $row['voksen-samarbeid'] );
		$this->setVoksenErfaring( $row['voksen-erfaring'] );
		$this->setVoksenAnnet( $row['voksen-annet'] );
		
		$this->setLydErfaring1( $row['lyd-erfaring-1'] );
		$this->setLydErfaring2( $row['lyd-erfaring-2'] );
		$this->setLydErfaring3( $row['lyd-erfaring-3'] );
		$this->setLydErfaring4( $row['lyd-erfaring-4'] );
		$this->setLydErfaring5( $row['lyd-erfaring-5'] );
		$this->setLydErfaring6( $row['lyd-erfaring-6'] );
		
		$this->setLysErfaring1( $row['lys-erfaring-1'] );
		$this->setLysErfaring2( $row['lys-erfaring-2'] );
		$this->setLysErfaring3( $row['lys-erfaring-3'] );
		$this->setLysErfaring4( $row['lys-erfaring-4'] );
		$this->setLysErfaring5( $row['lys-erfaring-5'] );
		$this->setLysErfaring6( $row['lys-erfaring-6'] );
		
		$this->sorry = $row['sorry'];
		
		if( !empty( $row['samarbeid'] ) ) {
			$this->setHarDeltakerskjema( true );
		}
		if( !empty( $row['voksen-samarbeid'] ) && !empty( $row['voksen-erfaring'] ) ) {
			$this->setHarVoksenskjema( true );
		}


	}
	
	public function getSorry() {
		return $this->sorry;
	}
	
	public function setLydtekniker( $bool ) {
		$this->type_lydtekniker = $bool;
		return $this;
	}
	public function getLydtekniker() {
		return $this->type_lydtekniker;
	}
	
	public function setLystekniker( $bool ) {
		$this->type_lystekniker = $bool;
		return $this;
	}
	public function getLystekniker() {
		return $this->type_lystekniker;
	}
	
	public function setVertskap( $bool ) {
		$this->type_vertskap = $bool;
		return $this;
	}
	public function getVertskap() {
		return $this->type_vertskap;
	}
	
	public function setProdusent( $bool ) {
		$this->type_produsent = $bool;
		return $this;
	}
	public function getProdusent() {
		return $this->type_produsent;
	}	

	
	public function setSamarbeid( $samarbeid ) {
		$this->samarbeid = $samarbeid;
		return $this;
	}
	public function getSamarbeid() {
		return $this->samarbeid;
	}
	
	public function setErfaring( $erfaring ) {
		$this->erfaring = $erfaring;
		return $this;
	}
	public function getErfaring() {
		return $this->erfaring;
	}
	
	public function setSuksesskriterie( $suksesskriterie ) {
		$this->suksesskriterie = $suksesskriterie;
		return $this;
	}
	public function getSuksesskriterie() {
		return $this->suksesskriterie;
	}
	
	public function setAnnet( $annet ) {
		$this->annet = $annet;
		return $this;
	}
	public function getAnnet() {
		return $this->annet;
	}

	public function setVoksenSamarbeid( $samarbeid ) {
		$this->voksen_samarbeid = $samarbeid;
		return $this;
	}
	public function getVoksenSamarbeid() {
		return $this->voksen_samarbeid;
	}
	
	public function setVoksenErfaring( $erfaring ) {
		$this->voksen_erfaring = $erfaring;
		return $this;
	}
	public function getVoksenErfaring() {
		return $this->voksen_erfaring;
	}
	
	public function setVoksenAnnet( $annet ) {
		$this->voksen_annet = $annet;
		return $this;
	}
	public function getVoksenAnnet() {
		return $this->voksen_annet;
	}
	
	
	public function setLydErfaring1( $erfaring ) {
		$this->lyderfaring1 = $erfaring;
		return $this;
	}
	public function getLydErfaring1() {
		return $this->lyderfaring1;
	}
	
	public function setLydErfaring2( $erfaring ) {
		$this->lyderfaring2 = $erfaring;
		return $this;
	}
	public function getLydErfaring2() {
		return $this->lyderfaring2;
	}
	
	public function setLydErfaring3( $erfaring ) {
		$this->lyderfaring3 = $erfaring;
		return $this;
	}
	public function getLydErfaring3() {
		return $this->lyderfaring3;
	}
	
	public function setLydErfaring4( $erfaring ) {
		$this->lyderfaring4 = $erfaring;
		return $this;
	}
	public function getLydErfaring4() {
		return $this->lyderfaring4;
	}
	
	public function setLydErfaring5( $erfaring ) {
		$this->lyderfaring5 = $erfaring;
		return $this;
	}
	public function getLydErfaring5() {
		return $this->lyderfaring5;
	}
	
	public function setLydErfaring6( $erfaring ) {
		$this->lyderfaring6 = $erfaring;
		return $this;
	}
	public function getLydErfaring6() {
		return $this->lyderfaring6;
	}
	
	
	public function setLysErfaring1( $erfaring ) {
		$this->lyserfaring1 = $erfaring;
		return $this;
	}
	public function getLysErfaring1() {
		return $this->lyserfaring1;
	}
	
	public function setLysErfaring2( $erfaring ) {
		$this->lyserfaring2 = $erfaring;
		return $this;
	}
	public function getLysErfaring2() {
		return $this->lyserfaring2;
	}
	
	public function setLysErfaring3( $erfaring ) {
		$this->lyserfaring3 = $erfaring;
		return $this;
	}
	public function getLysErfaring3() {
		return $this->lyserfaring3;
	}
	
	public function setLysErfaring4( $erfaring ) {
		$this->lyserfaring4 = $erfaring;
		return $this;
	}
	public function getLysErfaring4() {
		return $this->lyserfaring4;
	}
	
	public function setLysErfaring5( $erfaring ) {
		$this->lyserfaring5 = $erfaring;
		return $this;
	}
	public function getLysErfaring5() {
		return $this->lyserfaring5;
	}
	
	public function setLysErfaring6( $erfaring ) {
		$this->lyserfaring6 = $erfaring;
		return $this;
	}
	public function getLysErfaring6() {
		return $this->lyserfaring6;
	}
	

}
