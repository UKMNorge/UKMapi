<?php


class artikkel {
	var $id = null;
	var $pl_type = null;
	var $sesong	= null;
	var $blog_id = null;
	var $blog_url = null;
	var $innslag_id = null;
	var $innslag = null;
	var $tittel = null;
	var $link = null;


	public function __construct( $id_or_row ) {
		if( is_numeric( $id_or_row ) ) {
			$this->_loadById( $id_or_row );
		} else {
			$this->_loadByRow( $id_or_row );
		}
	}
	
	private function _loadById( $id ) {
		throw new Exception( 'ARTIKKEL: Ikke implementert _loadById() ');
		return $this->_loadByRow( $row );
	}
	
	private function _loadByRow( $row ) {
		$this->setId( $row['post_id'] );
		$this->setBlogId( $row['blog_id'] );
		$this->setBlogUrl( $row['blog_url'] );
		$this->setInnslagId( $row['b_id'] );
		$this->setSesong( $row['b_season'] );
		$this->setMonstringType( $row['pl_type'] );
		
		$post_meta = unserialize( $row['post_meta'] );
		$this->setTittel( base64_decode( $post_meta['title'] ) );
		$this->setLink( $post_meta['link'] );
	}


	/**
	 * Sett post-ID
	 *
	 * @param integer $id 
	 *
	 * @return $this
	**/
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	/**
	 * Hent post-ID
	 *
	 * @return integer $id
	**/
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Sett Blogg-id (wordpress)
	 *
	 * @param integer $blog_id
	 *
	 * @return $this
	**/
	public function setBlogId( $blog_id ) {
		$this->blog_id = $blog_id;
		return $this;
	}
	/**
	 * Hent Blogg-id (wordpress)
	 *
	 * @return integer $blog_id
	**/
	public function getBlogId() {
		return $this->blog_id;
	}

	/**
	 * Sett Blogg-url (wordpress)
	 *
	 * @param string $blog_url
	 *
	 * @return $this
	**/
	public function setBlogUrl( $blog_url ) {
		$this->blog_url = $blog_url;
		return $this;
	}
	/**
	 * Hent Blogg-url (wordpress)
	 *
	 * @return string $blog_url
	**/
	public function getBlogUrl() {
		return $this->blog_url;
	}	
	
	/**
	 * Sett tittel
	 *
	 * @param string $tittel
	 * @return $this
	**/
	public function setTittel( $tittel ) {
		$this->tittel = $tittel;
		return $this;
	}
	/**
	 * Hent tittel
	 *
	 * @return string $tittel
	 *
	**/
	public function getTittel() {
		return $this->tittel;
	}
	
	/**
	 * Sett link
	 *
	 * @param string $link
	 * @return $this
	**/
	public function setLink( $link ) {
		$this->link = $link;
		return $this;
	}
	/**
	 * Hent link
	 *
	 * @return string $link
	 *
	**/
	public function getLink() {
		return $this->link;
	}
	
	/** 
	 * Sett InnslagId
	 *
	 * @param int innslag_id
	 *
	 * @return $this;
	**/
	public function setInnslagId( $innslag_id ) {
		$this->innslag_id = $innslag_id;
		return $this;
	}
	
	/**
	 * Hent InnslagId
	 *
	 * @return int $innslag_id
	**/
	public function getInnslagId() {
		return $this->innslag_id;
	}
	
	/**
	 * Hent Innslag
	 *
	 * @return innslag
	**/
	public function getInnslag() {
		// Innslaget er allerede lastet
		if( null !== $this->innslag ) {
			return $this->innslag;
		}
		
		// Innslag er ikke spesifisert (burde ikke gå an)
		if( null == $this->getInnslagId() ) {
			return false;
		}
		
		require_once('UKM/innslag.class.php');
		$innslag = new innslag_v2( $this->getInnslagId() );
		
		// Innslaget finnes ikke
		if( null == $innslag->getId() ) {
			return false;
		}
		
		return $innslag;
	}
	
	/**
	 * Set sesong
	 *
	 * @param integer sesong
	 *
	 * @return this
	**/
	public function setSesong( $sesong ) {
		$this->sesong = $sesong;
		return $this;
	}
	
	/**
	 * Hent sesong
	 *
	 * @return integer sesong
	**/
	public function getSesong() {
		return $this->sesong;
	}


	/**
	 * Sett mønstringstype (pl_type)
	 * Brukes for å gruppere bilder, og det er unødvendig tungt å hente mønstringsobjektet for det
	 *
	 * @param string pl_type
	 * 
	 * @return $this
	**/
	public function setMonstringType( $pl_type ) {
		$this->pl_type = $pl_type;
		return $this;
	}
	
	/**
	 * Hent mønstringstype (pl_type)
	 * Brukes for å gruppere bilder, og det er unødvendig tungt å hente mønstringsobjektet for det
	 *
	 * @return string pl_type (kommune,fylke,land)
	**/
	public function getMonstringType() {
		return $this->pl_type;
	}



}	
/*
	if(isset($media['post']) && is_array($media['post'])) {
		foreach( $media['post'] as $artikkel ) {
			$a = new stdClass();
			$a->url 	= $artikkel['post_meta']['link'];
			$a->tittel 	= base64_decode($artikkel['post_meta']['title']);
			
			$data->artikler[] = $a;
		}
	}
*/