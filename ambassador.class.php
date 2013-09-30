<?php
require_once('UKM/monstring.class.php');

class ambassador {
	public function __construct($face_id=false) {
		$qry = new SQL("SELECT * FROM `ukm_ambassador`
					    WHERE `amb_faceID` = '#faceid'
					    ORDER BY `amb_firstname`,
						`amb_lastname` ASC"
						,
						array('faceid' => $face_id));
		$res = $qry->run('array');
		if(!$res && !$face_id)
			return false;
		
		$place = new monstring($res['pl_id']);
		$this->season = $place->get('season');
		$this->monstring = $place->get('pl_name');
		
		foreach($res as $key => $val) {
			$newkey = str_replace('amb_','',$key);
			$this->$newkey = is_string($val) ? utf8_encode($val) : $val;
		}
		
		$this->link = '//facebook.com/profile.php?id='.$this->faceID;
		$this->image = '//graph.facebook.com/'.$this->faceID.'/picture';
	}
}