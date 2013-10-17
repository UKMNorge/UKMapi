<?php

class urg {
	public function __construct($id) {
		$sql = new SQL("SELECT * FROM `ukmno_urg`
						WHERE `urg_id` = '#id'",
						array('id' => $id));
		$res = $sql->run('array');
		foreach($res as $key => $val) {
			$newkey = str_replace('urg_','', $key);
			if(is_string($val))
				$this->$newkey = utf8_encode($val);
			else
				$this->$newkey = $val;
		}
		
		
		$this->image = 'http://graph.facebook.com/'
					.  str_replace('profile.php?id=','',$this->facebook)
					.  '/picture?type=large';
					
		$this->facebook = new StdClass;
		$this->facebook->username = $res['urg_facebook'];
		$this->facebook->link = '//facebook.com/'. $this->facebook->username;
	}
}