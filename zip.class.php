<?php
class zip {
	var $debug = false;
	public function __construct($destination, $overwrite) {
		$destination = str_replace(' ','_', preg_replace("[^A-Za-z0-9?!]", "_", $destination).'.zip');
	
		$this->destination = $destination;
		$this->overwrite = $overwrite;

		$this->folder = ZIP_WRITE_PATH;
		$this->destination = $this->folder. $destination;
		$this->download = 'http://download.ukm.no/zip/'. basename($this->destination);
	
		if(!file_exists($this->folder))
			mkdir($this->folder);
			
		if(file_exists($this->destination) && !$this->overwrite)
			return $this->debug ? 'Fil finnes, overskriver ikke' : false;
	}
	
	public function debugMode() {
		$this->debug = true;
	}
	
	public function add($file, $nicename) {
		$this->files[$file] = $nicename;
		return file_exists($file);
	}
	
	public function run() {
		return $this->compress();
	}
	public function compress() {
		$valid_files = array();
		if(is_array($this->files)) {
			foreach($this->files as $file => $name) {
				if(file_exists($file)) {
					if( is_readable( $file ) ) {
						$valid_files[$file] = $name;
					} else {
						return $this->debug ? ('Fil ikke lesbar: '. $file) : false;
					}
	    		} else {
	    			return $this->debug ? ('Fil finnes ikke: '. $file) : false;
	    		}
			}
		}
		if(count($valid_files)) {
			$zip = new ZipArchive();
	    	$open = $zip->open($this->destination, $this->overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE);

			if($open !== true) {
	      		return $this->debug ? $this->_ZipStatusString($open) : false;
			}
			echo 'Legg til filer '. count($valid_files);
			foreach($valid_files as $file => $name) {
				$name = preg_replace("[^A-Za-z0-9?!]", "_", $name);
				echo 'DO ADD: '. $file .' AS '. $name .'<br />';
				#$res = $zip->addFile($file,$name);
			}
			$zip->close();

			return $this->download;
	  	}
		return $this->debug ? 'Ingen filer lagt til i komprimeringsliste' : false;
	}
	
	private function _ZipStatusString( $status ){
	    switch( (int) $status ) {
			case ZipArchive::ER_OK           : return 'N No error';
			case ZipArchive::ER_MULTIDISK    : return 'N Multi-disk zip archives not supported';
			case ZipArchive::ER_RENAME       : return 'S Renaming temporary file failed';
			case ZipArchive::ER_CLOSE        : return 'S Closing zip archive failed';
			case ZipArchive::ER_SEEK         : return 'S Seek error';
			case ZipArchive::ER_READ         : return 'S Read error';
			case ZipArchive::ER_WRITE        : return 'S Write error';
			case ZipArchive::ER_CRC          : return 'N CRC error';
			case ZipArchive::ER_ZIPCLOSED    : return 'N Containing zip archive was closed';
			case ZipArchive::ER_NOENT        : return 'N No such file';
			case ZipArchive::ER_EXISTS       : return 'N File already exists';
			case ZipArchive::ER_OPEN         : return 'S Can\'t open file';
			case ZipArchive::ER_TMPOPEN      : return 'S Failure to create temporary file';
			case ZipArchive::ER_ZLIB         : return 'Z Zlib error';
			case ZipArchive::ER_MEMORY       : return 'N Malloc failure';
			case ZipArchive::ER_CHANGED      : return 'N Entry has been changed';
			case ZipArchive::ER_COMPNOTSUPP  : return 'N Compression method not supported';
			case ZipArchive::ER_EOF          : return 'N Premature EOF';
			case ZipArchive::ER_INVAL        : return 'N Invalid argument';
			case ZipArchive::ER_NOZIP        : return 'N Not a zip archive';
			case ZipArchive::ER_INTERNAL     : return 'N Internal error';
			case ZipArchive::ER_INCONS       : return 'N Zip archive inconsistent';
			case ZipArchive::ER_REMOVE       : return 'S Can\'t remove file';
			case ZipArchive::ER_DELETED      : return 'N Entry has been deleted';
			
			default: return sprintf('Unknown status %s', $status );
		}
	}
}
?>
