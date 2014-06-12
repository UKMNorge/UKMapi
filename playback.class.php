<?php
class playback {
	var $home = '/home/ukmplayb/public_html/';
	var $url = 'http://playback.ukm.no/';
	
	public function __construct( $id ) {
		$sql = new SQL("SELECT *
						FROM `ukm_playback`
						WHERE `pb_id` = '#pbid'",
					   array('pbid' => $id)
					  );
		$res = $sql->run('array');
		
		foreach( $res as $key => $val ) {
			$new_key = str_replace('pb_', '', $key );
			$this->$new_key = utf8_encode($val);
		}
		
		$this->local_file();
		$this->download();
		$this->ext();
	}
	
	public function local_file() {
		$this->local_file = $this->home . 'upload/data/'. $this->season .'/'. $this->pl_id . '/'. $this->file;
		return $this->local_file;
	}
	
	public function download() {
		$this->download = $this->url . $this->id .'/'. $this->id .'/';
		return $this->download;
	}
	
	public function ext() {
		return $this->extension();
	}
	
	public function extension() {
		$extPos = strrpos($this->file, '.');
		$this->extension = $this->ext = substr($this->file, $extPos);
		return $this->ext;
	}
}
?>