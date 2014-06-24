<?php

require_once('UKM/curl.class.php');
require_once('UKM/sql.class.php');

class tv_images 
{
    protected $data = array();
	var $storageurl = 'http://video.ukm.no/';
	var $storageIP	= '212.125.231.33';
	var $storageurl2 = 'http://video2.ukm.no:88/';
	var $storageIP2	= '81.0.146.165';
    
    public function getData() 
    {
        $data = array();
        $query = new SQL('SELECT * FROM ukm_tv_files WHERE tv_img NOT IN (SELECT path FROM ukm_tv_img)');
        $result = $query->run();
        
        while($row = mysql_fetch_assoc($result)) {
            $data[] = $row;
        }
        $this->data = $data;
        return $data;
    }
    
    public function run($data = array()) 
    {
        /* If no data is injected, fetch default */
        if(!count($data)>0) {
            if(!count($this->data)>0) {
                $this->getData();
            }
            $data = $this->data;
        }
        
        foreach($data as $file) {
            $path = $file['tv_img'];
            //$server = $this->getServer($path);
            $server = $this->storageurl;
            $size = $this->getSize($server, $path);
            $width = $size['width'];
            $height = $size['height'];
            
            $sql = new SQLins('ukm_tv_img');
            $sql->add('server', $server);
            $sql->add('path', $path);
            $sql->add('width', $width);
            $sql->add('height', $height);
            
            $sql->run();
        }
    }
    
	public function getSize($server, $path) 
	{
		list($width, $height) = @getimagesize($server . $path);
		if(!is_numeric($width) || !is_numeric($height)) {
    		$width = 1280;
    		$height = 720;
		}
		$ratio = $width / $height;
		// Manuell 16:9 for ytelse
		//$ratio = 1.77777777777778;
		//$width = 1280;
		//$height = 720;
		
		if($width > 930) {
			$width = 930;
			$height = $width / $ratio;
		}
		$data['width'] = round($width);
		$data['height'] = round($height);
        return $data;
	}
	
	public function getServer($path)
	{
		global $UKMCURL;
		$curl = new UKMCURL();
		$curl->headersOnly();
		$res = $curl->request($this->storageurl . $path);
		
		if($res == 404) {
            $image_url = $this->storageurl2 . $path;
		} 
		else {
		    $image_url = $this->storageurl . $path;
		}
		return $image_url;
	}
}