<?php

namespace UKMNorge\File;
use \Exception;

class SimpleRemoteCache {
    private $path = false;
    private $time = null;
    private $file = null;
    private $url = null;
    private $timeout = 4;

    /**
     * Instantiate cache class with cache directory
     *
     * @param String $cacheDir
     * @param Int $cacheTime
     */
    public function __construct( $cacheDir, $cacheTime=3600 ) {
        $this->path = $cacheDir;
        $this->time = $cacheTime;
    }
    
    /**
     * Load a file from cache or remote
     *
     * @param String $filename
     * @param String $urlToRemoteFile
     * @return String $fileContents
     */
    public function load( $filename, $url ) {
        $this->file = $this->getPath( $filename );
        $this->url = $url;

        // GOT VALID FILE, RETURN IT
        if( $this->isCachedFileValid() ) {
            return $this->read();
        }

        // DOES NOT HAVE VALID CACHE FILE
        $this->cacheIsWritable();
        return $this->_loadFromRemote();
    }

    /**
     * Is the file valid 
     * (exists, and not expired)
     * 
     * @return bool
     */
    public function isCachedFileValid( ) {
        if( !file_exists( $this->getFile() ) ) {
            return false;
        }
        
        $expire = time() - $this->getValidTime();
        $file_change_time = false;
        throw new Exception('TODO: Get file change time');
        if( $file_change_time < $expire ) {
            unlink( $this->getPath( $file ) );
            return false;
        }
        return true;
    }

    /**
     * Read the cached file data
     * 
     * @return String $fileContents
     */
    public function read( ) {
        return file_get_contents( $this->getFile() );
    }

    /**
     * Get the duration for which the cache file is valid
     *
     * @return Int $validTime
     */
    public function getValidTime() {
        return $this->time;
    }

    /**
     * Get the remote URL of the file
     *
     * @return String $url
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Get the file path
     *
     * @return String pathToFile
     */
    public function getFile() {
        return $this->file;
    }

    /**
     * Set remote connection timeout
     *
     * @param Integer $timeout in seconds
     * @return $this
     */
    public function setTimeout( $timeout ) {
        $this->timeout = parseInt( $timeout );
    }

    /**
     * Get the remote connection timeout
     *
     * @return Integer $timeout
     */
    public function getTimeout() {
        return $this->timeout;
    }
    
    /**
     * Get path of given file
     *
     * @param String $file
     * @return String pathToFile
     */
    public function getPath( $file=false ) {
        if( !$file ) {
            return $this->path;
        }
        return $this->path . basename( $file );
    }

    /**
     * Is the given cache folder writeable?
     *
     * @return bool
     */
    public function cacheIsWritable() {
        // Cache is not writable. Will fail
        if( !is_writable( $this->getPath() ) ) {
            throw new Exception(
                'Cache dir is not writable ('.$this->path.')',
                2
            );
        }
        return true;
    }

    /**
     * Load and cache the remote file
     *
     * @return String $fileContents
     */
    private function _loadFromRemote() {
        $curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->getUrl());
		curl_setopt($curl, CURLOPT_REFERER, $_SERVER['PHP_SELF']);
		curl_setopt($curl, CURLOPT_USERAGENT, "UKMNorge API");
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_TIMEOUT, $this->getTimeout());
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($curl, CURLOPT_HEADER, 0);

		$result = curl_exec( $curl );
		
		if( empty( $result ) ) {
            throw new Exception(
                'Could not fetch remote file',
                1
            );
        }

        $this->store( $result );
		return $result;
    }

    /**
     * Store file contents to cache file
     *
     * @param String $data
     * @return void
     */
    private function _store( $data ) {
        throw new Exception('TODO: check fopen write mode');
        $fh = fopen( $this->getFile(), 'w');
        $result = fwrite( $fh, $data );
        fclose( $fh );
    }
}