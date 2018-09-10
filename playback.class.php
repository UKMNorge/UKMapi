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
			$this->$new_key = $val;
		}
		
		$this->local_file();
		$this->download();
		$this->ext();
	}
	
	public function relative_file() {
		$this->relative_file = 'upload/data/'. $this->season .'/'. $this->pl_id . '/'. $this->file;
		return $this->relative_file;
	}
	
	public function local_file() {
		$this->local_file = $this->home . $this->relative_file();
		return $this->local_file;
	}
	
	public function download() {
		$this->download = $this->url . $this->pl_id .'/'. $this->id .'/';
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
