<?php
require_once('UKM/monstring.class.php');

class ambassador {
	public function __construct($face_id=false) {
		$qry = new SQL("SELECT  *
						FROM `ukm_ambassador` AS `amb`
						LEFT JOIN `ukm_ambassador_skjorte` AS `skjorte`
							ON (`amb`.`amb_id` = `skjorte`.`amb_id`)
							WHERE `amb_faceID` = '#faceid'",
				array('faceid' => $face_id));

		$res = $qry->run('array');

		if(!$res && !$face_id)
			return false;
		
		if($face_id) {
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
	
	public function delete() {
		$sql = new SQLdel('ukm_ambassador', array('face_ID' => $this->faceID));
		$sql->add('deleted', 'true');
		echo $sql->debug();
	}
	
	
	public function invite($phone, $pl_id) {
		$pass =   chr(rand(97,122)) 
				. chr(rand(97,122))
				. rand(0,9)
				. rand(0,9)
				. strtoupper(chr(rand(65,90)))
				. strtoupper(chr(rand(65,90)))
				;
					
		$qry = new SQLins('ukm_ambassador_personal_invite');
		$qry->add('invite_phone', $phone);
		$qry->add('invite_code', $pass);
		$qry->add('invite_confirmed', 'false');
		$qry->add('pl_id', $pl_id);
		$res = $qry->run();
		
		if($res==-1)
			return array('phone' => $phone,
						 'success' => false,
						 'message' => 'Personen er allerede invitert');
		
		$message = 'Hei!
	Vi håper at du vil bli ambassadør for UKM. Du gjør så mye eller lite du vil :)
	Svar UKM AMB for å motta mer informasjon!
	Hilsen UKM Norge';
		
		require_once('UKM/sms.class.php');
		$SMS = new SMS('ambassador', false);
		$SMS->text($message)->to($phone)->from(1963)->ok();
		return array('phone' => $phone,
					 'success' => $res['error'] ? false : true,
					 'message' => $res['error'] ? $r['message'] : 'sendt');
	}
}
