<?php
require_once('UKM/sql.class.php');

class person {
	
	private $info = array();
	private $save_old = array();
	private $save_new = array();

	// Skrevet ny høst 2015 for bruk av UKMAPIBundle
	public function lagre( $system_id, $user_id, $pl_id=null ) {
		$this->_log_id( $system_id, $user_id, $pl_id );
		
		if( !is_numeric( $this->info['p_id'] ) ) {
			throw new Exception('Cannot save unknown participant!');
		}
		
		$sql_participant = new SQLins('smartukm_participant', array('p_id' => $this->info['p_id'] ) );
	
		$counter = 0;
		foreach( $this->save_new as $key => $val ) {
			if( $this->save_old[ $key ] == $val ) {
				continue;
			}
			$counter++;
			// Participant-tabellen
			if( strpos( $key, 'p_' ) === 0 ) {
				// LOG
				$this->_log( $key, $val );
				// Add to query and run
				$sql_participant->add( $key, $val );		
			} elseif( strpos($key, 'instrument') === 0 ) {
				// Dependencies
				if( !is_numeric( $this->info['b_id'] ) ) {
					throw new Exception('Cannot save instrument of P'. $this->info['p_id'] .' in unknown "innslag"');
				}
				// SQL relation table smartukm_rel_b_p
				$sql_instrument = new SQLins('smartukm_rel_b_p', array('p_id' => $this->info['p_id'], 'b_id' => $this->info['b_id'] ));
				// LOG
				$this->_log( 'instrument', $val );
				// Add to query and run
				$sql_instrument->add( $key, $val );
				//echo 'sql_i:'. $sql_instrument->debug();
				$sql_instrument->run();
			}
		}
		if($counter > 0) {
			//echo 'sql_p: '. $sql_participant->debug();
			$sql_participant->run();
		}
	}
	
	private function _log( $field, $newValue ) {
		// Finn log action ID
		$action = $this->_log_action( $field );
		// ActionID består av 1-2-sifret tall objektID konkatenert med 2-sifret ActionID
		// 101 = objekt 1, action 1
		$object = substr($action, 0, (strlen($action)-2));
		
	    $qry = new SQLins('log_log');
	    $qry->add('log_u_id', $this->log_user_id);
	    $qry->add('log_system_id', $this->log_system_id);
	    $qry->add('log_action', $action);
	    $qry->add('log_object', $object);
	    $qry->add('log_the_object_id', $this->info['p_id']);
	    $qry->add('log_pl_id', $this->log_pl_id);
		
		$res = $qry->run();
		$log_id = $qry->insid();
		
		if( !is_numeric( $log_id ) ) {
			throw new Exception('Cannot save participant due to log error');
		}
		// Logg ny verdi
		$qry = new SQLins('log_value');
		$qry->add('log_id', $log_id);
		$qry->add('log_value', $newValue );
		$qry->run();
		
	}
	
	private function _log_action( $field ) {
switch( $field ) {
	case 'instrument':
	$table = 'smartukm_rel_b_p';
	break;
	default:
	$table = 'smartukm_participant';
	break;
}
		$actionQ = new SQL("SELECT `log_action_id`
						FROM `log_actions` 
						WHERE `log_action_identifier` = '#identifier'",
						array('identifier'=>$table.'|'.$field));
		$actionID = $actionQ->run('field','log_action_id');
		
	    if( empty( $actionID ) ) {
		    throw new Exception('LOG FAILED. Object update failed due to missing '. $field .' log action error');
	    }
		
		return $actionID;
	}
	
	private function _log_id( $system_id, $user_id, $pl_id ) {
		$this->log_system_id = $system_id;
		$this->log_user_id = $user_id;
		$this->log_pl_id = $pl_id;
	}
		
	public function update($field, $post_key=false, $b_id=false) {
		if(!$post_key)
			$post_key = $field;
		if($_POST[$post_key] == $_POST['log_current_value_'.$post_key])
			return true;

		if ($field == 'td_konferansier') {
			$qry = new SQLins('smartukm_technical', array('pl_id'=>get_option('pl_id'), 'b_id'=>$b_id));
		}
		else if ($field != 'instrument') {
			$qry = new SQLins('smartukm_participant', array('p_id'=>$this->info['p_id']));
			UKMlog('smartukm_participant',$field,$post_key,$this->info['p_id']);
		}
		else {
			if( isset( $this->info['b_id'] ) && is_numeric( $this->info['b_id'] ) && $this->info['b_id'] > 0 ) {
				if( $b_id > 0 && $b_id != $this->info['b_id'] ) {
					$use_b_id = $b_id;
				} else {
					$use_b_id = $this->info['b_id'];
				}
			} else {
				$use_b_id = $b_id;
			}
			$test = new SQL("SELECT `p_id` FROM `smartukm_rel_b_p`
							 WHERE `p_id` = '#pid'
							 AND `b_id` = '#bid'",
							 array('pid' => $this->info['p_id'],
							 	   'bid' => $use_b_id));
			$test = $test->run();
			if(mysql_num_rows( $test ) == 0) {
				$qry = new SQLins('smartukm_rel_b_p');
				$qry->add('p_id', $this->info['p_id']);
				$qry->add('b_id', $use_b_id);
			} else {
				$qry = new SQLins('smartukm_rel_b_p', array('p_id'=>$this->info['p_id'], 'b_id'=>$use_b_id));
			}
			UKMlog('smartukm_rel_b_p',$field,$post_key,$this->info['p_id']);
		}
			
		$qry->add($field, $_POST[$post_key]);
//		echo $qry->debug();
		$qry->run();
	}
	
	public function create($b_id=false) {
		$qry = new SQLins('smartukm_participant');
		$qry->run();
		$this->info['p_id'] = $qry->insid();

		if($b_id)		
			$this->relate($b_id);
	}
	
	public function relate($b_id) {
		$innslag = new innslag($b_id);
		$innslag->addPerson($this->info['p_id']);
		
#		$innslag->person($this->info['p_id'], $b_id);
		$innslag->statistikk_oppdater();
	}
	
	public function unrelate($b_id) {
		$innslag = new innslag($b_id);
		$res = $innslag->removePerson($this->info['p_id']);
		
		$this->person($this->info['p_id'], $b_id);
		$innslag->statistikk_oppdater();
		return $res;
	}

	
	public function getNicePhone() {
		$phone = $this->g('p_phone');
		$phone = substr($phone, 0, 3).' '.substr($phone, 3, 2).' '.substr($phone, 5, 3);
		return $phone;
	}
	
	public function getNicePhoneWithColor() {
		$phone = $this->g('p_phone');
		if (strlen($phone) == 8 && (substr($phone, 0, 1) == 9 || substr($phone, 0, 1) == 4)) {
			$phoneClass = 'mobiltelefon';
		}
		else if (strlen($phone) == 8) {
			$phoneClass = 'telefon';
		}
		else {
			$phoneClass = 'ikke_telefon';
		}
		return '<span class="'.$phoneClass.'">'.$this->getNicePhone().'</span>';
	}

	public function loadGeo() {
		$geo = new SQL("SELECT `k`.`id` AS `kommuneID`,
							   `k`.`name` AS `kommune`,
							   `f`.`id` AS `fylkeID`,
							   `f`.`name` AS `fylke`
						FROM `smartukm_participant` AS `p`
						JOIN `smartukm_kommune` AS `k` ON (`k`.`id` = `p`.`p_kommune`)
						JOIN `smartukm_fylke` AS `f` ON (`f`.`id` = `k`.`idfylke`)
						WHERE `p_id` = '#pid'",
						array('pid'=>$this->info['p_id']));
		$geo = $geo->run('array');
	
		if(is_array($geo))
			$this->info = array_merge($this->info, $geo);
	}
	
	public function __construct( $p_id, $b_id = false) {
		if( false == $p_id || 0 == $p_id ) {
			$this->info['p_id'] = false;
			return;
		}
		
		// Hvis vi har Band ID, last inn fra database med band id
		if( is_numeric( $b_id ) ) {
			$this->_loadByPIDAndBID($p_id, $b_id);
		}
		
		// Hvis vi ikke har Band ID, eller innlasting med Band ID feilet
		if( !$this->info || !$b_id ) {
			$this->_loadByPID( $p_id );
		}
		
		// Korriger data for tegnsett og andre småfix
		$this->_postLoadFix($p_id);
	}


### Kommentert ut fordi det vil kunne finnes flere med samme tlfnr i tabellen.
/*	
public function getByPhone( $phone, $b_id=false ) {
		$p_id = $this->_getPIDFromPhone( $phone );
		$this->__construct( $p_id, $b_id );
	}
	private function _getPIDFromPhone( $phone ) {
		# HVA GJØR VI NÅR DET FINNES FLERE MED SAMME TLF?
		$sql = new SQL("SELECT `p_id` FROM `smartukm_participant`
						WHERE `p_phone` = '#phone'",
						array('phone' => $phone)
					);
		return $sql->run('field', 'p_id');
	}
	
	*/

	public function innslag() {
		$sql = new SQL("SELECT * FROM `smartukm_rel_b_p` WHERE `p_id` = '#pid'",
					   array('pid' => $this->info['p_id'])
					   );
		$res = $sql->run();
		$innslag = array();
		while( $r = mysql_fetch_assoc( $res ) ) {
			$innslag[] = $r['b_id'];
		}
		
		return $innslag;
	}

	public function isValid() {
		return is_numeric($this->info['p_id']) && $this->info['p_id'] > 0;
	}
	

	private function _loadByPID( $p_id ) {
		$qry = "SELECT `smartukm_participant`.`p_id`, `p_firstname`, `p_lastname`, `p_dob`, `p_phone`, `p_adress`,
			`p_postnumber`, `p_email`, `p_kommune` FROM `smartukm_participant`
			WHERE `smartukm_participant`.`p_id` = ".$p_id."
			GROUP BY `smartukm_participant`.`p_id` 
			ORDER BY `smartukm_participant`.`p_firstname`, `smartukm_participant`.`p_lastname` ASC";
			
		$qry = new SQL($qry);	
		//var_dump($qry->debug());
		$res = $qry->run( 'array' );
		if ($res) 
			{
			$this->info = $res;
		}
		else {
			$this->info['p_firstname'] = '';
			$this->info['p_lastname'] = '';
			$this->info['p_id'] = false;
			$this->info['name'] = null; //Empty anyway, hvorfor fylle inn?
			$this->info['p_phone'] = 0;
		}
	}
	
	private function _loadByPIDAndBID( $p_id, $b_id ) {
		$qry = "SELECT `smartukm_participant`.`p_id`,
					 `p_firstname`,
					 `p_lastname`,
					 `instrument`,
					 `instrument_object`,
					 `p_dob`,
					 `p_phone`,
					 `p_postnumber`,
					 `p_adress`,
					 `p_email`,
					 `p_kommune` 
					FROM `smartukm_participant`
					LEFT JOIN `smartukm_rel_b_p` ON (`smartukm_rel_b_p`.`p_id` = `smartukm_participant`.`p_id`)
					WHERE `smartukm_rel_b_p`.`b_id` = ".$b_id."
					AND `smartukm_rel_b_p`.`p_id` = ".$p_id."
					GROUP BY `smartukm_participant`.`p_id` 
					ORDER BY `smartukm_participant`.`p_firstname`, `smartukm_participant`.`p_lastname` ASC";
				
			$qry = new SQL($qry);
			#echo $qry->debug();
			$this->info = $qry->run( 'array' );
			if ($this->info !== false) {
				$this->info['b_id'] = $b_id;
			}
	}
	
	private function _postLoadFix($p_id) {
		$this->info['p_firstname'] = ucwords($this->info['p_firstname']);
		$this->info['p_lastname'] = ucwords($this->info['p_lastname']);
		$this->info['p_id'] = $p_id;
		$this->info['name'] = $this->info['p_firstname'] . ' ' . $this->info['p_lastname'];
	}

	public function alder() {
		return $this->getAge();
	}
	
	public function getAge($monstring=false) {
		$end_ts = false;
		if($this->info['p_dob'] == 0)
			return '25+';
		$start_ts = $this->info['p_dob'];
		if(function_exists('get_site_option'))
			$end_ts = get_site_option( 'ukm_pl_start' );

		if($monstring && get_class($monstring) == 'monstring') {
			$end_ts = $monstring->get('pl_start');
		}
		if(!$end_ts)
			$end_ts = time();

		$anniversary = date('z', $start_ts);
		$days_in_year = date('L', $start_ts) ? 366 : 365;
		$sec_per_day = 86400; // 60 * 60 * 24
		$diff = $end_ts - $start_ts;
		$diff -= $diff % $sec_per_day; // discard misc seconds
		$days = $diff / $sec_per_day; // number of days to iterate through;
		
		if( $diff < 0 )
		return false; // $start_ts is in the future!
		
		if( $days < $days_in_year ) // less than 1 year old; don't need to loop
		return 0;
		
		$years = 0;
		
		do {
			// add the remaining days left in the current year first.
			$left_in_year = $days_in_year - $anniversary;
			if( $days < $left_in_year )
			$left_in_year = $days;
			
			$start_ts += $sec_per_day * $left_in_year;
			$days -= $left_in_year;
			// are there enough days remaining to make it to the next birthday?
			if( $days > $anniversary ) {
				$years++; // increment years;
				$to_anniv = $anniversary;
			} else {
				$to_anniv = $days;
			}
			
			// add the days remaining to either the next birthday, or today's date, whichever
			// is closer.
			$start_ts += $sec_per_day * $to_anniv;
			$days -= $to_anniv;
			// reevaluate $days_in_year so that leap year is taken into account.
			$days_in_year = date('L', $start_ts) ? 366 : 365;
		} while( $days > 0 );
		
		// return the result.
		return $years;		
	}
	
	public function set($key, $value){
		if(!array_key_exists($key, $this->info)) {
			$this->info[$key] = null;
		}
		$this->save_old[ $key ] = $this->info[ $key ];
		$this->info[ $key ] = $value;
		$this->save_new[ $key ] = $value;
		
	}
	
	public function g($key) {
		return $this->get($key);
	}
	## Returnerer verdien til attributten (key)
	public function get($key, $encode = true) {
		if ($key == 'p_phone' && $this->info[$key] == 0)
			return '';
			
        if($encode)
		    return utf8_encode($this->info[$key]);	
		    
        return $this->info[$key];
	}
	
	public function videresendt( $pl_to_id, $tittel=false ) {
		if( $tittel ) {
			$videresendt = new SQL("SELECT *
									FROM `smartukm_fylkestep_p`
									WHERE `pl_id` = '#pl_id'
									AND `b_id` = '#b_id'
									AND `p_id` = '#p_id'
									AND `t_ids` LIKE '%#tittel%'",
									array('pl_id'=>$pl_to_id,
									      'b_id'=>$this->g('b_id'),
									      'p_id'=>$this->g('p_id'),
									      'tittel' => '|t_'. $tittel .'|'
									      )
								       );
		} else {
			$videresendt = new SQL("SELECT *
									FROM `smartukm_fylkestep_p`
									WHERE `pl_id` = '#pl_id'
									AND `b_id` = '#b_id'
									AND `p_id` = '#p_id'",
									array('pl_id'=>$pl_to_id,
									      'b_id'=>$this->g('b_id'),
									      'p_id'=>$this->g('p_id')
									      )
								       );
		}
		$videresendt = $videresendt->run();
		return !mysql_num_rows($videresendt) == 0;
	}
	
	public function videresend($videresendFra, $videresendTil, $tittel) {
		if (!($this->g('b_id') > 0) || !($videresendFra > 0) || !($videresendTil > 0))
			return false;
						
		$test_relasjon = new SQL("SELECT * FROM `smartukm_fylkestep_p`
								  WHERE `pl_id` = '#plid'
								  AND `b_id` = '#bid'
							      AND `pl_from` = '#pl_from'
								  AND `p_id` = '#p_id'",
								  array('plid'=>$videresendTil, 
								  		'bid'=>$this->g('b_id'), 
										'p_id'=>$this->g('p_id'), 
										'pl_from'=>$videresendFra));
		$test_relasjon = $test_relasjon->run();
		
		if(mysql_num_rows($test_relasjon)==0) {
			$videresend_person = new SQLins('smartukm_fylkestep_p');
			$videresend_person->add('pl_id', $videresendTil);
			$videresend_person->add('pl_from', $videresendFra);
			$videresend_person->add('b_id', $this->g('b_id'));
			$videresend_person->add('p_id', $this->g('p_id'));
			$videresend_person->add('t_ids', '|t_'. $tittel.'|');
			$videresend_person->run();
		} else {
			$row = mysql_fetch_assoc( $test_relasjon );
			$new_t_ids = $row['t_ids'] . '|t_'. $tittel .'|';
			$videresend_person = new SQLins('smartukm_fylkestep_p', 
											array('pl_id' => $videresendTil,
												  'pl_from' => $videresendFra,
												  'b_id' => $this->g('b_id'),
												  'p_id' => $this->g('p_id')
												 )
											);
			$videresend_person->add('t_ids', $new_t_ids);
			$videresend_person->run();
			
		}
		return true;
	}
	
	public function avmeld($videresendFra, $videresendTil, $tittel) {
		if (!($this->g('b_id') > 0) || !($videresendFra > 0) || !($videresendTil > 0))
			return false;

		$test_relasjon = new SQL("SELECT * FROM `smartukm_fylkestep_p`
								  WHERE `pl_id` = '#plid'
								  AND `b_id` = '#bid'
							      AND `pl_from` = '#pl_from'
								  AND `p_id` = '#p_id'",
								  array('plid'=>$videresendTil, 
								  		'bid'=>$this->g('b_id'), 
										'p_id'=>$this->g('p_id'), 
										'pl_from'=>$videresendFra));
		$test_relasjon = $test_relasjon->run();
		$row = mysql_fetch_assoc( $test_relasjon );
		$new_t_ids = str_replace('|t_'. $tittel .'|','', $row['t_ids']);
				
		// Hvis vedkommende ikke følger noen titler, fjern fra fylkestep
		if( empty( $new_t_ids ) ) {
			$videresend_person = new SQLdel('smartukm_fylkestep_p', 
											array('pl_id'=>$videresendTil,
												  'pl_from'=>$videresendFra,
												  'b_id'=>$this->g('b_id'),
												  'p_id'=>$this->g('p_id')));
			$res = $videresend_person->run();
		// Vedkommende følger en eller flere andre titler, fjern denne fra lista.
		} else {
			$videresend_person = new SQLins('smartukm_fylkestep_p', 
											array('pl_id' => $videresendTil,
												  'pl_from' => $videresendFra,
												  'b_id' => $this->g('b_id'),
												  'p_id' => $this->g('p_id')
												 )
											);
			$videresend_person->add('t_ids', $new_t_ids);
			$videresend_person->run();
		}
		return true;
	}
	
	public function getExistingPerson($firstname, $lastnname, $phone) {
		$qry = new SQL("SELECT `p_id` FROM `smartukm_participant` 
						WHERE `p_firstname`='#firstname' 
						AND `p_lastname`='#lastname' 
						AND `p_phone`='#phone'", 
						array('firstname'=>$firstname, 
							  'lastname'=>$lastnname, 
							  'phone'=>(int)$phone));
		$p_id = $qry->run('field', 'p_id');

		if (!$p_id)
			return false;
		return new person($p_id);
	}
	
	
	function kjonn() {
		$first_name = $this->get("p_firstname");
		$first_name = split(" ", str_replace("-", " ", $first_name) );
		$first_name = $first_name[0];
		
		$qry = "SELECT `kjonn`
				FROM `ukm_navn`
				WHERE `navn` = '" . $first_name ."' ";
		
		$qry = new SQL($qry);
		$res = $qry->run('field','kjonn');
		
		if ($res == null)
			$res = 'unknown';
		
		return $res;	
	}

	
}

?>
