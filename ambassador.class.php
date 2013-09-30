<?php

class ambassador {
	public function __construct($face_id) {
		$qry = new SQL("SELECT * FROM `ukm_ambassador`
					    WHERE `amb_faceID` = '#faceid'
					    ORDER BY `amb_firstname`,
						`amb_lastname` ASC"
						,
						array('faceid' => $face_id));
		$res = $qry->run('array');
		if(!$res)
			return false;
		foreach($res as $key => $val) {
			$this->str_replace('amb_','',$key) = $val;
		}
		
		$this->facelink = '//facebook.com/profile.php?id='.$this->faceID;
		$this->picture = '//graph.facebook.com/'.$this->faceID.'/picture';
	}
}