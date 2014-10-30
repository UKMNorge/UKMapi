<?php
require_once('UKM/monstring.class.php');
require_once('UKM/sql.class.php');

class ambassador {
	var $ID = false;
	
	public function __construct($face_id=false) {
		$qry = new SQL("SELECT  *
						FROM `ukm_ambassador` AS `amb`
						LEFT JOIN `ukm_ambassador_skjorte` AS `skjorte`
							ON (`amb`.`amb_id` = `skjorte`.`amb_id`)
							WHERE `amb_faceID` = '#faceid'",
				array('faceid' => $face_id));

		$res = $qry->run('array');

		if(!$res || !$face_id)
			return false;
		
		if($face_id) {
			$place = new monstring($res['pl_id']);
			$this->season = $place->get('season');
			$this->monstring = $place->get('pl_name');
			
			if(!is_array($res))
				return false;
			foreach($res as $key => $val) {
				$newkey = str_replace('amb_','',$key);
				$this->$newkey = is_string($val) ? utf8_encode($val) : $val;
			}
			
			$this->link = '//facebook.com/profile.php?id='.$this->faceID;
			$this->image = '//graph.facebook.com/'. $this->faceID .'/picture?type=large';
			$this->thumb = '//graph.facebook.com/'.$this->faceID.'/picture';

		}
	}
	
	public function updateFacebookId( $currentID, $newID ) {
		$qry = new SQLins('ukm_ambassador', array('amb_faceID' => $currentID) );
		$qry->add('amb_faceID', $newID);
		$res = $qry->run();
		return $res;
	}
	public function create( $faceID, $firstname, $lastname, $phone, $email, $gender, $birthday) {
		
		$qry = new SQL("SELECT `pl_id` FROM `ukm_ambassador_personal_invite`
						WHERE `invite_phone` = '#phone'
						AND `invite_confirmed` = 'true';",
						array('phone'=>$phone));
		$pl_id = $qry->run('field','pl_id');
		
		$create = new SQLins('ukm_ambassador');
		$create->add('amb_faceID',		$faceID);
		$create->add('amb_firstname', 	$firstname);
		$create->add('amb_lastname', 	$lastname);
		$create->add('amb_phone',		$phone);
		$create->add('amb_birthday', 	$birthday);
		$create->add('amb_email', 		$email);
		$create->add('amb_gender', 		$gender);
		$create->add('pl_id', 			$pl_id);
		$res = $create->run();
			
		// OPPDATER INVITASJONEN
		$invite = new SQLins('ukm_ambassador_personal_invite', array('invite_phone'=>$phone));
		$invite->add('invite_confirmed', 'used');
		$resinvite = $invite->run();
		
		$this->__construct( $faceID );
		
		return $this;
	}
	public function setAddress( $address, $postalcode, $postalplace ) {
		$qry = new SQLins('ukm_ambassador_skjorte');
		$qry->add('amb_id',		$this->getID());
		$qry->add('adresse',	$address);
		$qry->add('postnr',		$postalcode);
		$qry->add('poststed',	$postalplace);
		$res = $qry->run();
	}

	public function setSize( $size ) {
		$qry = new SQLins('ukm_ambassador_skjorte', array('amb_ID' => $this->getId() ) );
		$qry->add('amb_id',		$this->getId());
		$qry->add('size', 		$size );
		$res = $qry->run();
	}
	
	public function getId() {
		return $this->ID;
	}
	
	public function getFacebookId() {
		return $this->faceID;
	}
	
	public function getFirstname() {
		return $this->firstname;
	}
	public function getLastname() {
		return $this->lastname;
	}
	public function getPhone() {
		return $this->phone;
	}
	public function getEmail() {
		return $this->email;
	}
	
	public function getPlid() {
		return $this->pl_id;
	}
	public function getMonstringName() {
		return $this->monstring;
	}
	public function getImage() {
		return $this->image;
	}
	public function getLink() {
		return $this->link;
	}
	
	public function delete() {
		if(empty($this->faceID))
			return false;
		$sql = new SQLins('ukm_ambassador', array('amb_faceID' => $this->faceID));
		$sql->add('deleted', 'true');
		return $sql->run();
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
