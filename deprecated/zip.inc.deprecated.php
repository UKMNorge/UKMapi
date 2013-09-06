<?php
function create_zip($files = array(),$destination = '',$overwrite = false) {
	$debug = true;
	$folder = UKM_HOME.'../temp/zip/';
	$destination = $folder.$destination;

	if(!file_exists($folder))
		mkdir($folder);
		
	if(file_exists($destination) && !$overwrite)
		return $debug ? 'Fil finnes, overskriver ikke' : false;
	
	$valid_files = array();
	if(is_array($files)) {
		foreach($files as $file => $name) {
			if(file_exists($file)) {
				$valid_files[$file] = $name;
    		} else {
    			return $file;
    		}
		}
	}
	if(count($valid_files)) {
    	$zip = new ZipArchive();
    	$open = $zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE);
		if($open !== true) {
      		return $debug ? ZipStatusString($open) : false;
		}
		foreach($valid_files as $file => $name) {
			$zip->addFile($file,$name);
		}
		$zip->close();
		return file_exists($destination);
  	}
	return $debug ? 'Ingen filer skal legges til i zip-filen, feiler derfor' : false;
}

function ZipStatusString( $status )
{
    switch( (int) $status )
    {
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

?>