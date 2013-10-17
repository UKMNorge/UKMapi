<?php

class urg {
	public function __construct($id) {
		$sql = new SQL("SELECT * FROM `ukmno_urg`
						WHERE `urg_id` = '#id'",
						array('id' => $id));
		$res = $sql->run('array');
		foreach($res as $key => $val) {
			$newkey = str_replace(array('urg_','-'),'', $key);
			if(is_string($val))
				$this->$newkey = utf8_encode($val);
			else
				$this->$newkey = $val;
		}
		
		$name = explode(' ', $this->navn);
		$ant_names = sizeof($name);
		if($ant_names == 3)
			$firstname = $name[0];
		else
			$firstname = array_splice($name, 0, round($ant_names/2));
		
		$this->name = $this->navn;
		$this->first_name = implode(' ', $firstname);
		$this->last_name = str_replace($this->first_name, '', $this->name);
		
				
		$this->facebook = new StdClass;
		$this->facebook->username = $res['urg_facebook'];
		$this->facebook->link = '//facebook.com/'. $this->facebook->username;
		$this->facebook->image = new StdClass;
		$this->facebook->image->raw = 'http://graph.facebook.com/'
									. str_replace('profile.php?id=','',$this->facebook->username)
									. '/picture/';
		$this->facebook->image->square 			= $this->facebook->image->raw .'?width=100&height=100';
		$this->facebook->image->square_large	= $this->facebook->image->raw .'?width=200&height=200';
		$this->facebook->image->square_small 	= $this->facebook->image->raw .'?width=50&height=50';
		$this->facebook->image->normal  		= $this->facebook->image->raw .'';
		$this->facebook->image->large  			= $this->facebook->image->raw .'?type=large';
		
		var_dump($this);
	}
}