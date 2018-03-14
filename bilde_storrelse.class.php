<?php

class bilde_storrelse {
	var $file = null;
	var $width = null;
	var $height = null;
	var $mimetype = null;
	var $basepath = null;
	var $path_internal = null;
	var $path_external = null;
	
	public function __construct( $bildedata ) {
		if( defined('UKM_HOSTNAME') && UKM_HOSTNAME == 'ukm.dev' ) {
			$this->basepath = '/var/www/wordpress/';
		} elseif( defined('UKM_HOSTNAME') && UKM_HOSTNAME == 'ukm.no' ) {
			$this->basepath = '/home/ukmno/public_html/';
		} else {
			throw new Exception('BILDE_STORRELSE: Undefined constant UKM_HOSTNAME');
		}
		
		
		if( !is_array( $bildedata ) ) {
			throw new Exception('BILDE_STORRELSE: Bildedata ble ikke gitt som array');
		}
		
		$this->setFile( $bildedata['file'] );
		$this->setWidth( $bildedata['width'] );
		$this->setHeight( $bildedata['height'] );
		if( isset( $bildedata['mime-type'] ) ) {
			$this->setMimeType( $bildedata['mime-type'] );
		}
		$this->setInternalPath( $bildedata['path_int'] );
		$this->setExternalPath( $bildedata['path_ext'] );
	}
		
	/**
	 * Sett filbane (relativ)
	 *
	 * @param string file
	 *
	 * @return $this
	**/
	public function setFile( $file ) {
		$this->file = $file;
		return $this;
	}
	/**
	 * Hent filbane (relativ)
	 *
	 * @return string file
	**/
	public function getFile() {
		return $this->file;
	}
	
	/**
	 * Sett bildebredde (px)
	 *
	 * @param int width
	 *
	 * @return $this
	**/
	public function setWidth( $width ) {
		$this->width = $width;
		return $this;
	}
	/**
	 * Hent bildebredde (px)
	 *
	 * @return int bredde
	**/
	public function getWidth() {
		return $this->width;
	}
	
	/**
	 * Sett bildehøyde (px)
	 *
	 * @param int høyde
	 *
	 * @return $this
	**/
	public function setHeight( $height ) {
		$this->height = $height;
		return $this;
	}
	/**
	 * Hent bildehøyde (px)
	 *
	 * @return int høyde
	**/
	public function getHeight() {
		return $this->height;
	}
	
	/**
	 * Sett mimetype
	 *
	 * @param string mimetype
	 *
	 * @return $this
	**/
	public function setMimeType( $mimetype ) {
		$this->mimetype = $mimetype;
		return $this;
	}
	
	/**
	 * Hent mimetype
	 *
	 * @return string mimetype
	**/
	public function getMimeType() {
		return $this->mimetype;
	}
	
	/**
	 * Sett external path (URL-base)
	 *
	 * @param string external path
	 *
	 * @return $this
	**/
	public function setExternalPath( $path ) {
		$this->path_external = str_replace('http:','https:', rtrim($path, '/').'/');
		if( defined('UKM_HOSTNAME') && UKM_HOSTNAME == 'ukm.dev' ) {
			$this->path_external = str_replace('https:', 'http:', $this->path_external);
		}
		return $this;
	}
	/**
	 * Hent external path (URL-base)
	 *
	 * @return string url-base
	**/
	public function getExternalPath() {
		return $this->path_external;
	}

	
	/**
	 * Sett internal path (path-base)
	 *
	 * @param string internal path
	 *
	 * @return $this
	**/
	public function setInternalPath( $path ) {
		$this->path_internal = rtrim($path, '/').'/';
		return $this;
	}
	/**
	 * Hent internal path (path-base)
	 *
	 * @return string path-base
	**/
	public function getInternalPath() {
		return rtrim($this->basepath, '/').'/'. $this->path_internal;
	}
	
	/**
	 * Hent full URL
	 *
	 * @return url
	**/
	public function getUrl() {
		return $this->getExternalPath() . $this->getFile(); 
	}
	
	/**
	 * Hent full filbane
	 *
	 * @return filbane
	**/
	public function getPath() {
		return $this->getInternalPath() . $this->getFile(); 
	}
}