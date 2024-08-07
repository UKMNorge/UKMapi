<?php
/* TOD: marius skulle se på
if( !file_exists( DOWNLOAD_PATH_ZIP ) ) {
	mkdir( DOWNLOAD_PATH_ZIP, 0777, true );
}
*/
namespace UKMNorge\File;

use ZipArchive;
use Exception;

class Zip {
	var $debug = false;
	var $tryCatch = false;
	
	//var $maxNumFiles = 1000;
	//var $maxSizeFile = 104857600; // 100MB
	//var $maxSizeTotal = 1572864000; // 1500MB
	var $maxNumFiles = 1000;
	var $maxSizeFile = 1048576000; // 1000MB
	var $maxSizeTotal = 2000000000; // 2500MB
	
	var $countFiles = 0;
	var $countSize = 0;
	
	var $files = array();
	
	public function __construct( $destination, $overwrite ) {
		$this->overwrite = $overwrite;
		$this->filename = basename( str_replace(' ','_', preg_replace("[^A-Za-z0-9?!]", "_", $destination).'.zip') );
		$this->destination = DOWNLOAD_PATH_ZIP . $this->filename;
		$this->download = DOWNLOAD_URL_ZIP . $this->filename;
				
		if(file_exists($this->destination) && !$this->overwrite) {
			return $this->_error('Fil finnes, overskriver ikke', 10);
		}
	}
	
	public function tryCatchAdd() {
		$this->tryCatch = true;
	}
	
	public function debugMode() {
		$this->debug = true;
	}
	
	private function _error( $message, $code ) {
		if( $this->debug )
			return $message;
		if( $this->tryCatch ) {
			throw new Exception( $message, $code );
		}
		return false;
	}
	
	public function add($file, $nicename) {
		if( file_exists($file) ) {
			$size = filesize( $file );
			if( $size > $this->maxSizeFile ) {
				return $this->_error('Filen er for stor '. round(($size/(1024*1024)),1) .'MB mot maks '. round($this->maxSizeFile / (1024*1024),1) .'MB', 20);
			}
	
			$this->countSize += $size;
			if( $this->countSize > $this->maxSizeTotal ) {
				return $this->_error('Total størrelse for filer overskrider '. round($this->maxSizeTotal / (1024*1024),1).'MB', 21);
			}
		
			$this->files[$file] = $nicename;
			return true;
		}
		return $this->_error('Filen eksisterer ikke!', 22);
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
						return $this->_error('Fil ikke lesbar: '. $file, 23);
					}
	    		} else {
	    			return $this->_error('Fil finnes ikke: '. $file, 22);
	    		}
			}
		}
		if( sizeof( $valid_files ) > 0 ) {
			if( sizeof( $valid_files ) > $this->maxNumFiles ) {
				return $this->_error('Du prøver å legge til for mange filer (maks '. $this->maxNumFiles.')', 40);
			}
			$zip = new ZipArchive();
			if( !file_exists( $this->destination ) ) {
				$open = $zip->open($this->destination, ZipArchive::CREATE);
			} elseif( $this->overwrite ) {
				$open = $zip->open($this->destination, ZipArchive::OVERWRITE);
			} else {
				return $this->_error('Fil finnes, overskriver ikke', 10);
			}

			if($open !== true) {
	      		return $this->_error($this->_ZipStatusString($open) . ' => '. $this->destination, 11);
			}
			foreach($valid_files as $file => $name) {
				$zip->addFile($file,$name);
			}
			$zip->close();

			return $this->download;
	  	}
		return $this->_error('Ingen filer lagt til i komprimeringsliste', 1);
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
