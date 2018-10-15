<?php

	if( ! defined ( 'TABLE_UKM_TITLE_SCENE' ) )
		define( 'TABLE_UKM_TITLE_SCENE', 'smartukm_titles_scene' );

	/// INFO ABOUT TITLES
	class titleInfo {
		var $retur = array();
		public function titleInfo($b_id, $bt_form,$placetype='kommune',$forwardToPLID=false) {
			$sql = new SQL("SELECT * FROM `#form` WHERE `b_id` = '#bid'", array('form'=>$bt_form, 'bid'=>$b_id));
			$sql = $sql->run();
			#if($_SERVER['REMOTE_ADDR'] == '193.214.121.5') { echo '<pre>'; var_dump(SQL::numRows($sql)); echo '</pre>'; }
			
			if($sql&&SQL::numRows($sql)>0)
				while( $r = SQL::fetch( $sql ) ) {
					if($placetype=='fylke') {
						$sqlTest = new SQL("SELECT * FROM `smartukm_fylkestep` WHERE `b_id` = '#bid' AND `t_id` = '#tid'",
										array('bid'=>$b_id, 'tid'=>$r['t_id']));
						$sqlTest = $sqlTest->run();
						if(SQL::numRows($sqlTest)==0)
							continue;
					}
					if($placetype=='land') {
						$sqlTest = new SQL("SELECT * FROM `smartukm_fylkestep`
											WHERE `b_id` = '#bid'
											AND `t_id` = '#tid'
											AND `pl_id` = '#plid'",
										array('bid'=>$b_id, 'tid'=>$r['t_id'], 'plid'=>$forwardToPLID));
						$sqlTest = $sqlTest->run();
						if(SQL::numRows($sqlTest)==0)
							continue;					
					}
				/*	if($_SESSION['onlyForwardedToLand'] == true||(isset($_GET['_fake_this_is_land']) && $_GET['_fake_this_is_land'] == 'true')) {
						$sqlTest = new SQL("SELECT * FROM `smartukm_landstep` WHERE `b_id` = '#b_id' AND `t_id` = '#t_id'", array('b_id'=>$b_id, 't_id'=>$r['t_id']));
						$sqlTest = $sqlTest->run();
						if(SQL::numRows($sqlTest) == 0) continue;	
					} elseif($_SESSION['this_is_a_fylkesmonstring_report'] == true||$_SESSION['onlyForwardedFrom'] == true) {
						$sqlTest = new SQL('smartukm_fylkestep', array('b_id'=>$b_id, 't_id'=>$sql[2][$i]['t_id']));
						$sqlTest = $sqlTest->run();
						if($sqlTest[1] == 0) continue;
					} elseif($_SESSION['this_is_a_fylkesmonstring_report'] != false) {
						$sqlTest = new SQL('smartukm_fylkestep', array('b_id'=>$b_id, 'pl_id'=>$_SESSION['placeid'], 't_id'=>$sql[2][$i]['t_id']));
						$sqlTest = $sqlTest->run();
						if($sqlTest[1] == 0) continue;
					}*/

					switch($bt_form) {
						case 'smartukm_titles_scene' : 		$this->scene($r);			break;
						case 'smartukm_titles_exhibition': $this->exhibition($r);		break;
						case 'smartukm_titles_video' : 		$this->video($r);			break;
						case 'smartukm_titles_other' : 		$this->other($r);			break;
					}
				}
		}
		
#		public function g($key) {	return $this->get($key);	}
#		public function get($key) {
#			return $this->info[$key];
#		}
		

		public function getTitleArray() {
			return $this->retur;
		}
		
		private function scene($r) {
			$pos = sizeof($this->retur);
			$this->retur[$pos]['t_id']			= $r['t_id'];
			$this->retur[$pos]['type']			= 'scene';
			$this->retur[$pos]['name'] 			= $r['t_name'];
			$this->retur[$pos]['titleby'] 		= $r['t_titleby'];
			$this->retur[$pos]['musicby'] 		= $r['t_musicby'];
			$this->retur[$pos]['coreography']	= $r['t_coreography'];
			$this->retur[$pos]['type']			= '';
			$this->retur[$pos]['technique']		= '';
			$this->retur[$pos]['format']		= '';
			$this->retur[$pos]['comments']		= '';
			$this->retur[$pos]['madeby']		= '';
			$this->retur[$pos]['time']			= (int) $r['t_time'];
			$this->retur[$pos]['experience']	= '';
			$parentes = '';
			if($r['t_titleby'] == '.'||$r['t_titleby']=='...'||$r['t_titleby']=='....'||$r['t_titleby']=='.....'||$r['t_titleby']=='instrumental') $r['t_titleby'] = '';
			if($r['t_musicby'] == '.'||$r['t_musicby']=='...'||$r['t_musicby']=='....'||$r['t_musicby']=='.....') $r['t_musicby'] = '';
			
			if(empty($r['t_titleby']) && empty($r['t_musicby'])) $parentes = '';
			else {
				if(!empty($r['t_titleby'])) $parentes .= 'Tekst: '.$r['t_titleby'];
				if(!empty($r['t_musicby'])) $parentes .= ' Melodi: ' . $r['t_musicby'];
				if($r['t_titleby'] == $r['t_musicby']) $parentes = 'Tekst og melodi: '. $r['t_musicby'];
			}
			$this->retur[$pos]['parentes']		= $parentes;  
		}
		
		private function exhibition($r) {
			$pos = sizeof($this->retur);
			$this->retur[$pos]['t_id']			= $r['t_id'];
			$this->retur[$pos]['type']			= 'exhibition';
			$this->retur[$pos]['name'] 			= $r['t_e_title'];
			$this->retur[$pos]['titleby'] 		= '';
			$this->retur[$pos]['musicby'] 		= '';
			$this->retur[$pos]['coreography']	= '';
			$this->retur[$pos]['type']			= $r['t_e_type'];
			$this->retur[$pos]['technique']		= $r['t_e_technique'];
			$this->retur[$pos]['format']		= $r['t_e_format'];
			$this->retur[$pos]['comments']		= $r['t_e_comments'];
			$this->retur[$pos]['madeby']		= $r['t_e_made_by'];
			$this->retur[$pos]['time']			= 0;
			$this->retur[$pos]['experience']	= '';
			$parentes = '';
			if(!empty($r['t_e_type'])) 			$parentes .= 'Type: '.$r['t_e_type'];
			if(!empty($r['t_e_technique'])) 	$parentes .= ' Teknikk: ' . $r['t_e_technique'];
			$this->retur[$pos]['parentes']		= $parentes;  
			}
		
		private function video($r) {
			$pos = sizeof($this->retur);
			$this->retur[$pos]['t_id']			= $r['t_id'];
			$this->retur[$pos]['type']			= 'video';
			$this->retur[$pos]['name'] 			= $r['t_v_title'];
			$this->retur[$pos]['titleby'] 		= '';
			$this->retur[$pos]['musicby'] 		= '';
			$this->retur[$pos]['coreography']	= '';
			$this->retur[$pos]['type']			= 'Video';
			$this->retur[$pos]['technique']		= '';
			$this->retur[$pos]['format']		= $r['t_v_format'];
			$this->retur[$pos]['comments']		= $r['t_v_comments'];
			$this->retur[$pos]['madeby']		= $r['t_v_made_by'];
			$this->retur[$pos]['time']			= (int) $r['t_v_time'];
			$this->retur[$pos]['experience']	= '';
			$this->retur[$pos]['parentes']		= '';
		}	
		
		private function other($r) {
			$pos = sizeof($this->retur);
			$this->retur[$pos]['t_id']			= $r['t_id'];
			$this->retur[$pos]['type']			= 'other';
			$this->retur[$pos]['name'] 			= $r['t_o_function'];
			$this->retur[$pos]['titleby'] 		= '';
			$this->retur[$pos]['musicby'] 		= '';
			$this->retur[$pos]['coreography']	= '';
			$this->retur[$pos]['type']			= '';
			$this->retur[$pos]['technique']		= '';
			$this->retur[$pos]['format']		= '';
			$this->retur[$pos]['comments']		= $r['t_o_comments'];
			$this->retur[$pos]['madeby']		= '';
			$this->retur[$pos]['time']			= 0;
			$this->retur[$pos]['experience']	= $r['t_o_experience'];
			$this->retur[$pos]['parentes']		= '';
		}
	}

?>