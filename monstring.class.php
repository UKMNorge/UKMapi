<?php
require_once 'UKM/sql.class.php';
require_once 'UKM/statistikk.class.php';
require_once 'UKM/monstring_tidligere.class.php';
require_once 'UKM/innslag.collection.php';
require_once 'UKM/innslag_typer.class.php';


	## MØNSTRINGSOBJEKTET
	# OBS: Klassen mangler lagrefunksjon!
	class monstring{
		## Attributtkontainer
		var $info = array();
		var $innslag_loaded = false;
		var $innslag = array();
		var $band_types_allowed = false;
		
		
		public function update($field, $post_key=false) {
			if(!$post_key)
				$post_key = $field;
/*
			UKM_loader('private');
			if(UKM_private()) {
				echo '<pre>';	var_dump($_POST); echo '</pre>';
			}
*/
			if($_POST[$post_key] == $_POST['log_current_value_'.$post_key])
				return true;
			if(in_array($post_key, array('pl_kommune')))
				return true;
			
			$qry = new SQLins('smartukm_place', array('pl_id'=>$this->info['pl_id']));
			$qry->add($field, $_POST[$post_key]);
			$qry->run();
			UKMlog('smartukm_place',$field,$post_key);
#			return $qry->debug();
		}
		
		############################################
		## KONSTRUKTØR
		## Laster inn en mønstrings attributter fra PL_ID
		############################################
		public function monstring($pl_id){
			if(isset($_GET['log_monstring'])) {
				$start = microtime();
				error_log('MONSTRING START'. $start);
			}
			$qry = new SQL("SELECT * FROM `smartukm_place` WHERE `pl_id` = '#plid'", array('plid'=>$pl_id));
			$this->info = $qry->run('array');
			
			// TYPE MØNSTRING
			## Finn hvilken type mønstring dette er
			if($this->info['pl_fylke'] == 0)
				$this->info['type'] = 'kommune';
			elseif($this->info['pl_fylke'] == 123456789)
				$this->info['type'] = 'land';
			else
				$this->info['type'] = 'fylke';

			// FINN FYLKE
			if($this->info['type'] == 'fylke') {
				$fylke = new SQL("SELECT * FROM `smartukm_fylke` WHERE `id` = '#id'", 
								array('id'=>$this->info['pl_fylke']));
				$fylke = $fylke->run('array');
				if(!$fylke)
					array('id'=>21, 'name'=>'Testfylke');
				$this->info['fylke_id'] = $fylke['id'];
				$this->info['fylke_name'] = $fylke['name'];
				
				$kommuner = new SQL("SELECT * FROM `smartukm_kommune`
									 WHERE `idfylke` = '#fylke'
									 ORDER BY `name` ASC",
									 array('fylke' => $this->info['fylke_id']));
				$kommuner = $kommuner->run();
				while( $r = mysql_fetch_assoc( $kommuner ) ) {
					$this->info['kommuner_i_fylket'][$r['id']] = utf8_encode($r['name']);
				}
			}
			
			// FINN KOMMUNER
			if($this->info['type'] == 'kommune') {
				$kommuner = new SQL("SELECT `kommune`.`id`, `kommune`.`name`, `kommune`.`idfylke`
									 FROM `smartukm_rel_pl_k` AS `rel`
									 JOIN `smartukm_kommune` AS `kommune` ON (`rel`.`k_id` = `kommune`.`id`)
									 WHERE `rel`.`pl_id` = '#plid'",
									 array('plid'=>$pl_id));
				$kommuner = $kommuner->run();
				$idfylke = false;
				while($r = mysql_fetch_assoc($kommuner)) {
					if(!$idfylke)
						$idfylke = $r['idfylke'];
					$this->info['kommuner'][] = array('id'=>$r['id'], 'name'=>utf8_encode($r['name']));
				}
				
				$fylke = new SQL("SELECT * FROM `smartukm_fylke` WHERE `id` = '#id'",
								array('id'=>$idfylke));
				$fylke = $fylke->run('array');
				$this->info['fylke_id'] = $fylke['id'];
				$this->info['fylke_name'] = $fylke['name'];
			}
			$this->_fellesmonstring();
			$this->info['calendar'] = false;
			$this->_link();
		}
		
		############################################
		## Gi ny verdi (value) til attributten (key)
		## OBS: Lagrer ikke!
		############################################
		public function set($key, $value){
			$this->info[$key] = $value;
		}
		
		############################################
		## Returnerer verdien til attributten (key)
		############################################
		public function g($key) {return $this->get($key);}
		public function get($key) {
		    if( !isset( $this->info[$key] ) ) {
    		    return false;
		    }
			return is_array($this->info[$key]) 
					? $this->info[$key]
					: utf8_encode($this->info[$key]);
		}
		
		############################################
		## Returnerer et person-array med deltakere tilknyttet mønstringen
		############################################
		public function getPersons() {
			$kommuneArr = $this->kommuneArray();
			foreach($kommuneArr as $k_id => $kommune)
				$kommuner[] = '`b_kommune` = '.$k_id;
			
			$or = implode(" OR ", $kommuner);
			
			$sql = new SQL("SELECT `smartukm_participant`.`p_id` FROM `smartukm_participant`
							JOIN `smartukm_rel_b_p` ON `smartukm_rel_b_p`.`p_id` = `smartukm_participant`.`p_id`
							JOIN `smartukm_band` ON `smartukm_rel_b_p`.`b_id` = `smartukm_band`.`b_id`
							WHERE `b_status` = 8 
							AND
							 (".$or.")
							ORDER BY `smartukm_participant`.`p_firstname`, `smartukm_participant`.`p_lastname`");	
								
			$result = $sql->run();
			
			$personer = array();
			if($result) {
				while($row = mysql_fetch_assoc($result)) {
					$personer[$row['p_id']] = new person($row['p_id']);
				}
			}
			return $personer;
		}

		############################################
		## Returnerer et kommune-array som kan brukes i select-lister
		############################################
		public function kommuneArray() {
			if(isset($this->info['kommuner'])) {
				for($i=0; $i<sizeof($this->info['kommuner']); $i++) {
					$kommuner[$this->info['kommuner'][$i]['id']] = $this->info['kommuner'][$i]['name'];
				}
				return $kommuner;
			} else {
				return $this->info['kommuner_i_fylket'];
			}
		}
		
		
		public function fylkeArray() {
			$sql = new SQL("SELECT * FROM `smartukm_fylke` ORDER BY `name` ASC");
			$res = $sql->run();
			while($r = mysql_fetch_assoc($res)) {
				$this->alle_fylker[$r['id']] = $r['name'];
			}
			return $this->alle_fylker;
		}
		
		############################################
		## Returnerer hele objektet for var-dump
		############################################
		public function info(){
			return $this->info;
		}
		

		##########################################################################################
		##			FUNKSJONER RELATERT TIL START, STOPP, FRISTER (MØNSTRINGENS TIDSPKT)		##
		##########################################################################################
		########################################  PRIVATE  #######################################

		############################################
		## Hjelpefunksjon for starter og slutter
		############################################
		private function _startstop($what) {
			if($this->info[$what] == 0)
				return 'Tidspunkt ikke registrert';
			
			return date('d.m.Y', $this->info[$what]) . ' kl. ' . date('H:i', $this->info[$what]);
		}
		
		################################################
		## Laster inn en kalender for start/stopp
		################################################
		private function _load_calendar() {
			UKM_loader('calendar');
	
			$start = explode('.', date('d.m.Y.H.i', $this->g('pl_start')));
			$stop = explode('.', date('d.m.Y.H.i', $this->g('pl_stop')));
	
			$maned = $start[1];
			$ar	   = $start[2];
	
			$title = $this->g('type')=='fylke' ? 'Fylkesm&oslash;nstringen' : 'Tid og sted';

			if($stop[0] >= $start[0]) {
				for($i=$start[0]-1; $i<$stop[0]; $i++)
					$days[$i+1] = array(null, 'selected', null);
			
				$this->info['calendar'] = '<span style="font-size: 11px; padding-top: 2px;">'
						.'Starter '.$start[0].'.'.$start[1].' kl '.$start[3].':'.$start[4]
						.'<br />'
						.'</span>'
						. generate_calendar($ar, $maned, $days)
						;
				return;
			## Sluttdato er mindre enn startdato, ergo er sluttdato neste måned!
			} else {
				for($i=$start[0]-1; $i<cal_days_in_month(CAL_GREGORIAN, $maned, $ar); $i++)
					$days1[$i+1] = array(null, 'selected', null);
				for($i=0; $i<$stop[0]; $i++) 
					$days2[$i+1] = array(null, 'selected', null);
		
				$this->info['calendar'] = '<span style="font-size: 11px; padding-top: 2px;">'
						.'Starter '.$start[0].'.'.$start[1].' kl '.$start[3].':'.$start[4]
						.'<br />'
						.'</span>'
						. generate_calendar($ar, $maned, $days1)
						. generate_calendar($ar, $maned+1, $days2)
						;
				return;
		}
		$this->info['calendar'] = '<span style="font-size: 12px;">'
			.  '<strong>Starter: </strong>' . $this->starter()
			.  '<br />'
			.  '<strong>Slutter: </strong>' . $this->slutter()
			.  '</span>';
		}

		########################################   PUBLIC  #######################################

		############################################
		## Er mønstringen i det heletatt registrert, eller er den bare opprettet?
		############################################
		public function registered() {
			return !$this->info['pl_start'] == 0;
		}

		############################################
		## Er det mulig å melde seg på denne mønstringen?
		## Sjekker default frist 1, kan spesifiseres til frist 2
		############################################
		public function subscribable($deadline = 'pl_deadline') {
			return $this->info[$deadline] > time();
		}

		############################################
		## Er mønstringen åpnet for påmelding?
		############################################
		public function subscriptionOpened() {
			return time() > $this->info['pl_deadline2'];
		}

		############################################
		## Er mønstringen aktiv akkurat nå? (BOOL)
		############################################
		public function aktiv() {
			return (time() > $this->info['pl_start']) && (time() < $this->info['pl_stop']);
		}

		############################################
		## Når starter mønstringen? KLARTEKST!
		############################################
		public function starter() {
			return $this->_startstop('pl_start');
		}

		############################################
		## Når slutter mønstringen? KLARTEKST!
		############################################
		public function slutter() {
			return $this->_startstop('pl_stop');
		}

		############################################
		## Er mønstringen avsluttet? (BOOL)
		############################################
		public function ferdig() {
			return $this->info['pl_stop'] < time();
		}
		
		############################################
		## Når er fristen for mønstringen? KLARTEKST!
		############################################
		public function frist($hvilken='') {
			return $this->_startstop('pl_deadline'.($hvilken==2?'2':''));
		}

		############################################
		## Sjekk om en innslagstype er tillatt på mønstringen
		############################################
		public function bandTypeAllowed( $bt_id ) {
			if( !isset( $this->band_types_allowed ) or empty( $this->band_types_allowed ) ) {
				$this->_load_bandTypes();
			}
	        	return isset($this->band_types_allowed[ $bt_id ] );
		}



		public function calendar() {
			if(!$this->g('calendar'))
				$this->_load_calendar();
		
			return $this->g('calendar');
		}
		
		public function dager() {
			$start = explode('.', date('d.m.Y.H.i', $this->g('pl_start')));
			$stop = explode('.', date('d.m.Y.H.i', $this->g('pl_stop')));
			
			$maned = $start[1];
			$ar	   = $start[2];
			
			if($stop[0] >= $start[0]) {
				for($i=$start[0]-1; $i<$stop[0]; $i++)
					$days[($i+1).'.'.$maned] = date('N', mktime(0,0,0,$maned, $i+1, $ar));
					
				return sizeof($days);
			## Sluttdato er mindre enn startdato, ergo er sluttdato neste måned!
			} else {
				for($i=$start[0]-1; $i<cal_days_in_month(CAL_GREGORIAN, $maned, $ar); $i++)
					$days[($i+1).'.'.$maned] = date('N', mktime(0,0,0,$maned, $i+1, $ar));
				for($i=0; $i<$stop[0]; $i++) 
					$days[($i+1).'.'.$maned+1] = date('N', mktime(0,0,0,$maned+1, $i+1, $ar));
				
				return sizeof($days);
			}
		}
		public function netter() {
			if( isset( $this->netter ) ) {
				return $this->netter;
			}
			$netter = array();
			
			$start = (int)$this->g('pl_start');
			$stop  = (int)$this->g('pl_stop');
			
			if( date('m', $start ) < date('m', $stop ) ) {
				$dager_ny_mnd = date('d', $stop);
				$dager_forrige_mnd = cal_days_in_month( CAL_GREGORIAN, date('m', $start), date('Y', $start)) - date('d', $start);
				$num_dager = $dager_ny_mnd + $dager_forrige_mnd;
			} else {
				$num_dager = date('d',$stop) - date('d',$start);
			}
//			$num_dager = floor( ($stop - $start) /(60*60*24));
			
			$crnt = new stdClass();
			$crnt->dag = (int)date('d', $start);
			$crnt->mnd= (int)date('m', $start);
			$crnt->ar  = (int)date('Y', $start);
			
			for( $i=0; $i < $num_dager+1; $i++ ) {	
				if( $crnt->dag > cal_days_in_month( CAL_GREGORIAN, $crnt->mnd, $crnt->ar ) ) {
					$crnt->dag = 1;
					$crnt->mnd++;
				}
				$crnt->timestamp = strtotime( $crnt->dag.'-'.$crnt->mnd.'-'.$crnt->ar );
				$netter[] = clone $crnt;
			
				$crnt->dag++;
			}
			$this->netter = $netter;
			return $this->netter;
		}


		##########################################################################################
		##		INNSLAGSTYPER (BAND TYPES), OG HVILKE SOM ER TILLATT FOR DENNE MØNSTRINGEN		##
		##########################################################################################
		########################################  PRIVATE  #######################################

		############################################
		##  Last inn hvilke innslagstyper som er tillatt for denne mønstringen		
		############################################
		private function _load_bandTypes() {
			$query = new SQL("SELECT `smartukm_band_type`.`bt_id`, 
							`bt_name`, 
							`bt_image`, 
							`bt_deadline`
						 	FROM `smartukm_band_type`
							JOIN `smartukm_rel_pl_bt` ON `smartukm_rel_pl_bt`.`bt_id` = `smartukm_band_type`.`bt_id`
							WHERE `smartukm_rel_pl_bt`.`pl_id`='#pl_id'",
					array('pl_id'=>$this->info['pl_id'],'season'=>$this->info['season']));
			$result = $query->run();

			## LOOP OG SETT VARIABLER FOR HVA SOM ER TILLATT
			if($result) {
				while($row = mysql_fetch_assoc($result)) {
					$row['bt_id'] = (int) $row['bt_id'];
					$row['bt_name'] = utf8_encode($row['bt_name']);
					$this->band_types_allowed[$row['bt_id']] = $row;
				}
			}
			
			
			// ADDED 23.11.2010 - makes subscription possible for the three mandatory band types
			// Opens for suscription even though the local contact person did not register the place
			$qry = new SQL("SELECT `bt_id`, 
							`bt_name`, 
							`bt_image`, 
							`bt_deadline`
						 	FROM `smartukm_band_type`
							WHERE `bt_id`<'4'");
			$default = $qry->run();
			
			while($r = mysql_fetch_assoc($default)) {
				$r['bt_id'] = (int) $r['bt_id'];
				$row['bt_name'] = utf8_encode($row['bt_name']);
				$this->band_types_allowed[$r['bt_id']] = $r;
			}
			// EOF ADDED 23.11.2010
		}
		
		############################################
		## Last inn alle innslagstyper
		############################################
		private function _loadAllBandTypes() {
			if(!$this->band_types_allowed)
				$this->_load_bandTypes();

			$qry = new SQL("SELECT `smartukm_band_type`.`bt_id`,
						   `smartukm_band_type`.`bt_name`
					FROM `smartukm_band_type` 
					WHERE `bt_id` != '10'
					AND `bt_id` != '7'
					ORDER BY `smartukm_band_type`.`bt_id` ASC"
					);
			$res = $qry->run();
					
			while($r = mysql_fetch_assoc($res))
				$this->all_band_types[] = array('bt_name'=>utf8_encode($r['bt_name']),
												'bt_id'=>(int)$r['bt_id'],
												'allowed'=>isset($this->band_types_allowed[(int)$r['bt_id']])
												);
		}


		########################################   PUBLIC  #######################################
		
		############################################		
		## Last inn og returner en liste over tillatte innslagstyper
		############################################
		public function getBandTypes() {
			if(!$this->band_types_allowed)
				$this->_load_bandTypes();

			return $this->band_types_allowed;		
		}

		############################################		
		## Last inn og returner en liste over alle innslagstyper
		############################################		
		public function getAllBandTypes() {
			if(!isset($this->all_band_types) || !is_array($this->all_band_types))
				$this->_loadAllBandTypes();
				
			return $this->all_band_types;
		}
		
		############################################
		## Returnerer en kommune-liste og javascriptfunksjoner
		############################################
		public function getSubscriptionHelper() {
			if(!$this->g('fellesmonstring'))
				return '';
				
			$return = '<script type="text/javascript" language="javascript">'
					.  "function velgKommune(fil, type, pl) {
							jQuery('#subscriptionIcons').hide();
							var links = jQuery('#subscriptionHelperInner').html();
							links = links.replace(/#TYPE#/g, type).replace(/#STEG#/g,fil).replace(/#PLID#/g,pl);
							jQuery('#subscriptionHelperInner').html(links);
							jQuery('#subscriptionHelper').show();
					   }"
					.  '</script>'
					.  '<div id="subscriptionHelperInner" style="padding-left: 20px;">'
					.  '<br />'
					.  '<strong>Velg din kommune for &aring; starte p&aring;meldingen:</strong>'
					.  '<br />'
					;
			
			foreach($this->info['kommuner'] as $kname => $k)
				$return .=' &nbsp; &nbsp; &nbsp; ' 
						. '<a href="http://pamelding.'.(defined('UKM_HOSTNAME')?UKM_HOSTNAME:'ukm.no').'/'
				  		. 'quickstart.php?steg=#STEG#&type=#TYPE#&plid=#PLID#'
				  		. '&kommune='.$k['id']
				  		. '" style="text-decoration:none; color: #000;">'
				  		. $k['name']
				  		. '</a><br />';
			
			$return .= '<div style="height:120px;"></div>'
					.  '</div>';
			
			return $return;
		}
		
        public function pameldingsikoner() {
            //UKM_loader('pamelding/config');
            $BANDTYPES = $this->getAllBandTypesDetailedNew();

            ## SJEKKER OM MAN HAR LASTET INN LISTEN OVER TILLATTE TYPER INNSLAG
            if(!$this->band_types_allowed)
                    $this->_load_bandTypes();
            
            ########
            #### TYPE "VANLIGE INNSLAG"
            ########

            ########
            #### FINN KOMMUNE(R ) I MØNSTRINGEN
            if(sizeof($this->info['kommuner'])==1)
                    $kommune = $this->info['kommuner'][0]['id'];

            $data = array();
            ## LOOPER ALLE "VANLIGE" TYPER INNSLAG
            foreach($BANDTYPES['regular'] as $i => $bt) {
                ## HVIS TYPEN IKKE ER TILLATT, HOPP OVER
                if(!isset($this->band_types_allowed[$bt['bt_id']]))
                    continue;
                
                $data['ikoner_tittel'][] = $bt;
            }
            
            ########
            #### TYPE "ANDRE INNSLAG" - JOBBE MED UKM
            ########
            foreach($BANDTYPES['work'] as $i => $bt) {
                ## HVIS TYPEN IKKE ER TILLATT, HOPP OVER
                if(!isset($this->band_types_allowed[$bt['bt_id']]))
                    continue;
                
                $data['ikoner_tittel_los'][] = $bt;
            }
            return $data;
        }
                
		############################################
		## Returner en liste over alle påmeldingsikoner for tillatte typer innslag
		############################################
		public function getSubscriptionIcons($deadlines=true, $clickable=true) {
			//UKM_loader('pamelding/config');
			$BANDTYPES = $this->getAllBandTypesDetailed();
			
			## SJEKKER OM MAN HAR LASTET INN LISTEN OVER TILLATTE TYPER INNSLAG
			if(!$this->band_types_allowed)
				$this->_load_bandTypes();

			########
			#### TYPE "VANLIGE INNSLAG"
			########
			## SJEKKER OM FRISTEN ER UTE
			$subscribable = $this->subscribable();
			
			########
			#### FINN KOMMUNE(R ) I MØNSTRINGEN
			if(sizeof($this->info['kommuner'])==1)
				$kommune = $this->info['kommuner'][0]['id'];
			
			## INITIERER CONTAINER FOR "VANLIGE" TYPER INNSLAG
			if($deadlines)
				$CONTENT_ADD1 = '<p style="margin-top: 0px; color: #ff0000; font-size:12px;">'
							  . 'P&aring;meldingsfrist: ' . $this->frist(1)
							  . (!$subscribable ? ' - <strong>Fristen er ute</strong>' : '')
							  .'</p>';
			else
				$CONTENT_ADD1 = '<br />';
						  			
			## LOOPER ALLE "VANLIGE" TYPER INNSLAG
			foreach($BANDTYPES['regular'] as $i => $bt) {
				## HVIS TYPEN IKKE ER TILLATT, HOPP OVER
				if(!isset($this->band_types_allowed[$bt['bt_id']]))
					continue;

				if($this->g('fellesmonstring'))
					$link = '<a href="'
							. 'javascript:velgKommune('
							. "'kontaktperson','".$bt['ico']."',".$this->get('pl_id')
					  		. ');" '
					  		. 'style="text-decoration:none; color: #000;">';
				else
					$link = '<a href="http://pamelding.'.(defined('UKM_HOSTNAME')?UKM_HOSTNAME:'ukm.no').'/'
								  		. 'quickstart.php?steg=kontaktperson'
								  		. '&type='.$bt['ico']
								  		. '&kommune='.$kommune
								  		. '&plid='.$this->get('pl_id')
								  		. '" '
								  		. 'style="text-decoration:none; color: #000;">';
				
				$counter_1++;
				## LEGGER TIL BOKS
				$CONTENT_ADD1 .= '<div style="'
									.'width: 90px;'
									.'height: 70px;'
									.'float: left;'
									.'text-align:center;'
									.'vertical-align: top;'
									.'padding: 2px;'
									.'margin: 0px;'
									.'font-size: 10px;'
									.'line-height: 13px;'
								.'">'
							  . (($_SERVER['REMOTE_ADDR']=='83.108.246.31') || ($subscribable && $clickable)
							  		? $link
							  		: ($clickable ? '<span style="color: #f52626;">' : '<span>')
							  	)
							  . '<img width="50" src="http://ico.ukm.no/subscription/'.$bt['ico'].'.png" '
							   .'style="border: 0px none; margin: 0px;" alt="'.$bt['title'].'" title="'.$bt['title'].'" />'
							   . '<br><span style="font-size:11px">'
						  	  . $bt['name']
						  	  . '</span>'
							  . (($_SERVER['REMOTE_ADDR']=='83.108.246.31') || ($subscribable && $clickable)
						  	  	? '</a>'
						  	  	: '</span>')
						  	  .'</div>'; 
			}
			$CONTENT = $CONTENT_ADD1;
			
			########
			#### TYPE "ANDRE INNSLAG" - JOBBE MED UKM
			########
			## SJEKKER OM FRISTEN ER UTE
			$subscribable = $this->subscribable('pl_deadline2');
			
			
			$CONTENT_ADD2 = '<br clear="all" />';
			if($deadlines)
				$CONTENT_ADD2 .='<p style="margin-top: 0px; color: #ff0000; font-size:12px;">'
							  . 'P&aring;meldingsfrist: ' . $this->frist(2)
							  . (!$subscribable ? ' - <strong>Fristen er ute</strong>' : '')
							  .'</p>';
			  
			$work_counter = 0;	
			foreach($BANDTYPES['work'] as $i => $bt) {
				## HVIS TYPEN IKKE ER TILLATT, HOPP OVER
				if(!isset($this->band_types_allowed[$bt['bt_id']]))
					continue;
				## TELL VIDERE FOR Å SE OM DET BLIR NOEN "ANDRE INNSLAG"-MULIGHETER
				$work_counter++;

				if($this->g('fellesmonstring'))
					$link = '<a href="'
							. 'javascript:velgKommune('
							. "'profilside_enk','".$bt['ico']."',".$this->get('pl_id')
					  		. ');" '
					  		. 'style="text-decoration:none; color: #000;">';
				else
					$link = '<a href="http://pamelding.'.(defined('UKM_HOSTNAME')?UKM_HOSTNAME:'ukm.no').'/'
					  		. 'quickstart.php?steg=profilside_enk'
					  		. '&type='.$bt['ico']
					  		. '&kommune='.$kommune
					  		. '&plid='.$this->get('pl_id')
							. '" '
					  		. 'style="text-decoration:none; color: #000;">';

				
				$CONTENT_ADD2 .= '<div style="'
									.'width: 90px;'
									.'height: 70px;'
									.'float: left;'
									.'text-align:center;'
									.'vertical-align: top;'
									.'padding: 2px;'
									.'margin: 0px;'
									.'font-size: 10px;'
									.'line-height: 13px;'
								.'">'
							  . (($_SERVER['REMOTE_ADDR']=='83.108.246.31') || ($subscribable && $clickable)
							  		? $link
							  		: ($clickable ? '<span style="color: #f52626;">' : '<span>')
							  	)
							  . '<img width="50" src="http://ico.ukm.no/subscription/'.$bt['ico'].'.png" '
							   .'style="border: 0px none; margin: 0px;" alt="'.$bt['title'].'" title="'.$bt['title'].'" />'
							   . '<br><span style="font-size:11px">'
						  	  . $bt['name']
						  	  . '</span>'
							  . (($_SERVER['REMOTE_ADDR']=='83.108.246.31') || ($subscribable && $clickable)
						  	  	? '</a>' 
						  	  	: '</span>')
						  	  .'</div>'; 
			}
			## LEGG TIL "ANDRE INNSLAG" HVIS DET ER NOEN
			#if($work_counter > 0) 
				$CONTENT .= $CONTENT_ADD2;
			
			return $CONTENT;
		}
		
		
		public function getAllBandTypesDetailed() {
			## MULIGE TYPER INNSLAG
			$BANDTYPES['regular'][] = array('bt_id'=>2, 
                                            'name'=>'Film', 
                                            'ico'=>'video', 
                                            'ico_url'=>'http://ico.ukm.no/pamelding/video.png', 
                                            'ico_url_gray'=>'http://ico.ukm.no/pamelding/video_gray.png', 
                                            'title'=>'Driver du og lager film, og har lyst til &aring; vise verden hva du driver med? Klikk her');
			$BANDTYPES['regular'][] = array('bt_id'=>3, 
                                            'name'=>'Utstilling', 
                                            'ico'=>'utstilling', 
                                            'ico_url'=>'http://ico.ukm.no/pamelding/utstilling.png',  
                                            'ico_url_gray'=>'http://ico.ukm.no/pamelding/utstilling_gray.png',
                                            'title'=>'Maler du bilder, er du fotograf, bygger du skulpturer? Klikk her');
			$BANDTYPES['regular'][] = array('bt_id'=>1, 
                                            'name'=>'Musikk', 
                                            'ico'=>'scene', 
                                            'ico_url'=>'http://ico.ukm.no/pamelding/scene.png', 
                                            'ico_url_gray'=>'http://ico.ukm.no/pamelding/scene_gray.png', 
                                            'title'=>'Synger du i dusjen, spiller du i band eller spiller du klaver? Det er plass til alle p&aring; UKM! Klikk her');
			$BANDTYPES['regular'][] = array('bt_id'=>1, 
                                            'name'=>'Dans', 
                                            'ico'=>'dans', 
                                            'ico_url'=>'http://ico.ukm.no/pamelding/dans.png', 
                                            'ico_url_gray'=>'http://ico.ukm.no/pamelding/dans_gray.png', 
                                            'title'=>'Jazz? Lyrisk? Hip-hop? Breakdance? Ballett? L&aelig;rt en dans p&aring; skolen? Laget din egen? Klikk her');
			$BANDTYPES['regular'][] = array('bt_id'=>1, 
                                            'name'=>'Teater', 
                                            'ico'=>'teater', 
                                            'ico_url'=>'http://ico.ukm.no/pamelding/teater.png', 
                                            'ico_url_gray'=>'http://ico.ukm.no/pamelding/teater_gray.png', 
                                            'title'=>'Bor det en skuespiller i deg som vil ut og opp p&aring; scenen? Nasjonalteateret neste, UKM f&oslash;rst! Klikk her');
			$BANDTYPES['regular'][] = array('bt_id'=>1, 
                                            'name'=>'Litteratur', 
                                            'ico'=>'litteratur', 
                                            'ico_url'=>'http://ico.ukm.no/pamelding/litteratur.png', 
                                            'ico_url_gray'=>'http://ico.ukm.no/pamelding/litteratur_gray.png', 
                                            'title'=>'Har du skrevet historien om deg selv i atten bind? Eller p&aring to linjer? Har du en korttekst i SMS-format? Klikk her');
			$BANDTYPES['regular'][] = array('bt_id'=>1, 
                                            'name'=>'Annet p&aring; scenen',
                                            'ico'=>'annet', 
                                            'ico_url'=>'http://ico.ukm.no/pamelding/annet.png', 
                                            'ico_url_gray'=>'http://ico.ukm.no/pamelding/annet_gray.png', 
                                            'title'=>'Driver du med en form for scenekunst som ikke passer i de andre kategoriene? Sjonglering? Flammesluking? Slangetemming? Klikk her');
		
			$BANDTYPES['regular'][] = array('bt_id'=>6, 
                                        'name'=>'Matkultur', 
                                        'ico'=>'matkultur', 
                                        'ico_url'=>'http://ico.ukm.no/pamelding/matkultur.png', 
                                        'ico_url_gray'=>'http://ico.ukm.no/pamelding/matkultur_gray.png', 
                                        'title'=>'Lyst til &aring; imponere et dommerpanel med din egen kokkelering? Klikk her');
			$BANDTYPES['work'][] = array('bt_id'=>4, 
                                         'name'=>'Konferansier', 
                                         'ico'=>'konferansier', 
                                         'ico_url'=>'http://ico.ukm.no/pamelding/konferansier.png', 
                                         'ico_url_gray'=>'http://ico.ukm.no/pamelding/konferansier_gray.png', 
                                         'title'=>'Ikke redd for &aring; prate i store forsamlinger? Lyst til &aring; gj&oslash;re det med mikrofon og publikum i din hule h&aring;nd? Klikk her');
			$BANDTYPES['work'][] = array('bt_id'=>5, 
                                         'name'=>'UKM Media', 
                                         'ico'=>'nettredaksjon', 
                                         'ico_url'=>'http://ico.ukm.no/pamelding/nettredaksjon.png', 
                                         'ico_url_gray'=>'http://ico.ukm.no/pamelding/nettredaksjon_gray.png', 
                                         'title'=>'Har du lyst til &aring; jobbe som journalist under m&oslash;nstringen? Eller til &aring; v&aelig;re kameramann under en forestilling? Eller produsere hele showet? Klikk her og finn ut hvilke muligheter som finnes p&aring; din m&oslash;nstring!');
			$BANDTYPES['work'][] = array('bt_id'=>8, 
                                         'name'=>'Arrang&oslash;r', 
                                         'ico'=>'arrangor', 
                                         'ico_url'=>'http://ico.ukm.no/pamelding/arrangor.png', 
                                         'ico_url_gray'=>'http://ico.ukm.no/pamelding/arrangor_gray.png', 
                                         'title'=>'Lyst til &aring; bidra til &aring; lage m&oslash;nstring sammen med din lokale arrang&oslash;r? Klikk her');
/*
			$BANDTYPES['work'][] = array('bt_id'=>9, 
                                         'name'=>'Sceneteknikk', 
                                         'ico'=>'sceneteknikk', 
                                         'ico_url'=>'http://ico.ukm.no/pamelding/sceneteknikk.png', 
                                         'ico_url_gray'=>'http://ico.ukm.no/pamelding/sceneteknikk_gray.png', 
                                         'title'=>'Liker du deg bedre bak scenen enn p&aring;? Har du lyst til &aring; bygge scene og rigge lyd og lys sammen med profesjonelle folk? Klikk her');
*/
			return $BANDTYPES;
		}
                
                // Not just the ico-name, but also the path (used in TWIG-design)
                public function getAllBandTypesDetailedNew() {
			$bandTypes = $this->getAllBandTypesDetailed();
			
			$bandTypes['regular'][0]['ico'] = 'film';
			$bandTypes['regular'][2]['ico'] = 'musikk';
			return $bandTypes;
		}
		
		##########################################################################################
		##						FUNKSJONER RELATERT TIL INNSLAG PÅ MØNSTRINGEN					##
		##########################################################################################
		########################################  PRIVATE  #######################################

		############################################
		## Finner alle innslag som deltar i mønstringen
		############################################
		private function load_innslag() {
			$bands = $this->_load_innslag_loop(false,8);
			for($i=0; $i<sizeof($bands); $i++) {
				$set = $bands[$i];
				if($this->g('type')=='land')
					$geonokkel = utf8_encode($set['fylke']);
				else
					$geonokkel = utf8_encode($set['kommune']);#$k_id['id'];
				$set['b_name'] = utf8_encode($set['b_name']);
				$infos = array('b_id'=>$set['b_id'],
							   'b_status'=>$set['b_status'],
							   'bt_id'=>$set['bt_id'],
							   'bt_form'=>$set['bt_form'],
							   'b_name'=>$set['b_name']
							   );
				$this->innslag[] = 	$this->innslag_alpha[] 
								 =	$this->innslag_bid[$set['b_id']]
								 =	$this->innslag_bt[$set['bt_id']][] 
								 =  $this->innslag_geo[$geonokkel][$set['b_id']] = $infos;
			}					
		}
		
		private function _load_innslag_qry($kommune, $status,$felt=false,$videresendte=false) {
			if(!$felt)	
				$get = '`smartukm_band`.`b_id`, `smartukm_band`.`bt_id`, `smartukm_band`.`b_status`, `smartukm_band`.`b_name`  ';
			else
				for($i=0; $i<sizeof($felt); $i++)
					$get .= '`'.$felt[$i].'`, ';
			$get = substr($get,0,strlen($get)-2);

			// PRE 2011 DID NOT USE BAND SEASON FIELD
			if($this->info['season'] <= 2011) {
				return "SELECT ". $get .", `bt`.`bt_form`, `k`.`name` AS `kommune`
						FROM `smartukm_band`
						JOIN `smartukm_band_type` AS `bt` ON (`bt`.`bt_id` = `smartukm_band`.`bt_id`)
						JOIN `smartukm_rel_pl_b` AS `pl_b` ON (`pl_b`.`b_id` = `smartukm_band`.`b_id`)
						LEFT JOIN `smartukm_kommune` AS `k` ON (`k`.`id`=`smartukm_band`.`b_kommune`)
						WHERE `pl_b`.`pl_id` = '".$this->get('pl_id')."'
						AND `b_status` = '". $status ."'";
			}


			// Brukes hvis vi søker videresendte fra min mønstring til en annen
			if($videresendte)
				return "SELECT ".$get.", `bt`.`bt_form`, `k`.`name` AS `kommune`
						FROM `smartukm_fylkestep` AS `fs` 
						JOIN `smartukm_band` ON (`smartukm_band`.`b_id` = `fs`.`b_id`)
						JOIN `smartukm_band_type` AS `bt` ON (`bt`.`bt_id` = `smartukm_band`.`bt_id`)
						JOIN `smartukm_kommune` AS `k` ON (`k`.`id` = `smartukm_band`.`b_kommune`)
						WHERE `b_season` = '".$this->info['season']."'
						AND `b_status` = '".$status."'
						AND `fs`.`pl_id` = '".$this->videresendTil()."'
						AND `fs`.`pl_from` = '".$this->info['pl_id']."'
						GROUP BY `smartukm_band`.`b_id`
						ORDER BY `bt_id` ASC,
						`b_name` ASC";

			if($this->g('type') == 'land')
				return "SELECT ".$get.", `bt`.`bt_form`, `f`.`name` AS `fylke`
						FROM `smartukm_fylkestep` AS `fs` 
						JOIN `smartukm_band` ON (`smartukm_band`.`b_id` = `fs`.`b_id`)
						JOIN `smartukm_band_type` AS `bt` ON (`bt`.`bt_id` = `smartukm_band`.`bt_id`)
						JOIN `smartukm_kommune` AS `k` ON (`k`.`id` = `smartukm_band`.`b_kommune`)
						JOIN `smartukm_fylke` AS `f` ON (`f`.`id` = `k`.`idfylke`)
						WHERE `b_season` = '".$this->info['season']."'
						AND `b_status` = '".$status."'
						AND `fs`.`pl_id` = '".$this->g('pl_id')."'
						GROUP BY `smartukm_band`.`b_id`
						ORDER BY `bt_id` ASC,
						`b_name` ASC";

						
			if($this->info['type']=='fylke')
				return "SELECT ".$get.", `bt`.`bt_form`, `k`.`name` AS `kommune`
						FROM `smartukm_band`
						JOIN `smartukm_band_type` AS `bt` ON (`bt`.`bt_id` = `smartukm_band`.`bt_id`)						
						JOIN `smartukm_kommune` AS `k` ON (`k`.`id`=`smartukm_band`.`b_kommune`)
						JOIN `smartukm_fylkestep` AS `f` ON (`f`.`b_id` = `smartukm_band`.`b_id`)
						WHERE `b_season` = '".$this->info['season']."'
						AND `b_status` = '".$status."'
						AND `k`.`idfylke` = '".$this->info['fylke_id']."'
						GROUP BY `smartukm_band`.`b_id`
						ORDER BY `b_name` ASC";
			if(is_array($this->info['kommuner'])) {
				$where = '';
				foreach($this->info['kommuner'] as $t => $k) {
					$where .= " (`b_season` = '".$this->info['season']."' "
									."AND `b_status` = '".$status."' "
									."AND `b_kommune` = '".$k['id']."') OR";
				}
				$where = substr($where,0,strlen($where)-3);
			}

			return "SELECT ".$get.", `bt`.`bt_form`, `k`.`name` AS `kommune`
					FROM `smartukm_band`
					JOIN `smartukm_band_type` AS `bt` ON (`bt`.`bt_id` = `smartukm_band`.`bt_id`)
					LEFT JOIN `smartukm_kommune` AS `k` ON (`k`.`id`=`smartukm_band`.`b_kommune`)
					WHERE ".$where."
					GROUP BY `smartukm_band`.`b_id`
					ORDER BY `b_name` ASC";
/*
			return "SELECT ".$get."
					FROM `smartukm_band`
					WHERE `b_kommune` = '".$kommune."'
					AND `b_season` = '".$this->info['season']."'
					AND `b_status` = '".$status."'
					GROUP BY `smartukm_band`.`b_id`
					ORDER BY `b_name` ASC";
*/
		}
		
		private function _load_innslag_loop($kommune, $status,$felt=false,$videresendte=false) {
			## Looper alle innslag fra kommunen med den gitte statusen
			$qry = new SQL($this->_load_innslag_qry($kommune, $status,$felt,$videresendte));
			$res = $qry->run();
			$bands = array();
			if($res)
			while($set = mysql_fetch_assoc($res)) {
				## Hopper over hvis innslaget er logget avmeldt (og status tilfeldigvis glemt oppdatert (bug høsten 2011))
				if(!$this->_load_innslag_clean($set))
					continue;
				$bands[] = $set;
			}
			return $bands;		
		}
		
		
		private function _load_innslag_clean($set) {
			$avmeldtest = new SQL("SELECT `log_id` FROM `ukmno_smartukm_log`
								   WHERE `log_b_id` = '#bid'
								   AND `log_code` = '23'
								   LIMIT 1",
							   array('bid'=>$set['b_id']));
			$avmeldtest= $avmeldtest->run('field', 'log_id');
			return !(is_numeric($avmeldtest) && $avmeldtest > 0);
		}
		
		########################################   PUBLIC  #######################################

		############################################
		## Returnerer en liste over innslags-ID'er. Hvis listen ikke er laget, last den inn
		############################################
		public function innslag() {
			if(!$this->innslag_loaded)
				$this->load_innslag();
				
			return $this->innslag;	
		}
		
		############################################
		## Returnerer en alfabetisk liste over alle innslag i mønstringen
		############################################
		public function innslag_alpha() {
			if(!$this->innslag_loaded)
				$this->load_innslag();
			return $this->innslag_alpha;
		}

		############################################
		## Returnerer en liste over innslag i mønstringen, sortert stigende etter b_id?
		############################################
		public function innslag_btid() {
			if(!$this->innslag_loaded)
				$this->load_innslag();
			return $this->innslag_bt;
		}		

		############################################
		## Returnerer en liste over innslag i mønstringen, gruppert etter geonøkkel (kommune / fylke)
		############################################
		public function innslag_geo() {
			if(!$this->innslag_loaded){
				$this->load_innslag();
				ksort($this->innslag_geo);
			}
			return $this->innslag_geo;
		}		

		############################################
		## Returnerer en liste over innslag i mønstringen, sortert stigende etter b_id?
		############################################
		public function innslag_last($limit=5) {
			if(!$this->innslag_loaded)
				$this->load_innslag();
			
			if(!is_array($this->innslag_bid))
				return false;
			
			krsort($this->innslag_bid);
			
			$i=0;
			foreach($this->innslag_bid as $b_id => $info) {
				if((int)$info['b_status']!==8)
					continue;
					
				if($i<$limit)
					$return[] = $info;
				else
					break;
				$i++;
			}
			return $return;
		}
		
		public function innslag_etter_status($status) {
			$bands = array();
			#if(is_array($this->info['kommuner'])) {
			#	foreach($this->info['kommuner'] as $trash => $k_id) {
					$res = $this->_load_innslag_loop($k_id['id'],$status,array('b_id'));
					if(is_array($res)) {
						$bands = array_merge($bands,$res);
					}
			#	}
			#}
			return $bands;
		}

		public function statistikk() {
            $this->statistikk = new statistikk();

			if($this->get('type')=='kommune') {
                $kommune_id = array();
                foreach ($this->info['kommuner'] as $key => $value) {
                    $kommune_id[] = $value['id'];
                }
                $this->statistikk->setKommune($kommune_id);
            } elseif($this->get('type')) {
				$this->statistikk->setFylke($this->get('pl_fylke'));
            } else {
	            $this->statistikk->setLand();
            }
            return $this->statistikk;
		}


		public function statistikk_pameldte($other_season=false,$today_selected_year=false) {
			$select_season = ($other_season ? $other_season : $this->g('season'));
			$personer = $innslag = 0;
			#if($_SERVER['REMOTE_ADDR']=='193.91.207.86')
			#	$table = 'smartukm_stat_realtime_join_kontor';
			#else
			
			if($today_selected_year) {
				$findoldtoday = new SQL("SELECT `s_id`
										FROM `smartukm_stat_realtime`
										WHERE `s_dato` LIKE '#dato%'
										ORDER BY `s_id` DESC
										LIMIT 1",
										array('dato'=>$select_season.'-'.date('m-d')));
				$oldtoday = $findoldtoday->run('field', 's_id');
			} else
				$oldtoday = 0;

			## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ##
			## !!!!!!!! OBS    !! 			
			## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ## ##
			## MIDLERTIDIG FIKS - MÅ INKLUDERE SEASON I REALTIME JOIN
			## 01.11.2012
			$table = 'smartukm_stat_realtime_join';

			

			$ureg = new SQL("SELECT `pl_missing`
							 FROM `smartukm_place`
							 WHERE `pl_id` = '#plid'",
							 array('plid'=>$this->info['pl_id']));
			$personer = (int)$ureg->run('field','pl_missing');
			
			## LOKALMØNSTRING
			if($this->info['type']=='kommune') {
				foreach($this->info['kommuner'] as $i => $info){
					#if($_SERVER['REMOTE_ADDR']=='148.122.12.118')
					#	echo $info['id'].'<br />';
					/*
$test = new SQL("SELECT `s_id` AS `personer`
									FROM `smartukm_stat_realtime_join`
									WHERE `k_id` = '#kommune'
									AND `p_id` != '0'
									AND `status` = '8'",
									array('kommune'=>$info['id']));	
*/			
					$qry = new SQL("SELECT COUNT(`s_id`) AS `personer`
									FROM `".$table."`
									WHERE `k_id` = '#kommune'
									AND `p_id` != '0'
									AND `status` = '8'
									AND `season` = '#season'"
									.($today_selected_year ? "AND `s_id` < '#oldtoday'" : ''),
									array('kommune'=>$info['id'], 'season'=>$select_season, 'oldtoday'=> $oldtoday ));
					$personer += (int)$qry->run('field','personer');
				
					$qry = new SQL("SELECT COUNT(`s_id`) AS `innslag`
									FROM `".$table."`
									WHERE `k_id` = '#kommune'
									AND `p_id` = '0'
									AND `status` = '8'
									AND `season` = '#season'"
									.($today_selected_year ? "AND `s_id` < '#oldtoday'" : ''),
									array('kommune'=>$info['id'], 'season'=>$select_season, 'oldtoday'=> $oldtoday ));

					$innslag += (int)$qry->run('field','innslag');
				}
			## VI SNAKKER OM FYLKESMØNSTRING E.L
			} else {
				$ureg = new SQL("SELECT SUM(`temp`.`missing`) AS `ureg`
								FROM (
									SELECT DISTINCT(`place`.`pl_id`),
										   (`place`.`pl_missing`) AS `missing`
									FROM `smartukm_place` AS `place` 
									JOIN `smartukm_rel_pl_k` AS `rel` ON (`rel`.`pl_id`=`place`.`pl_id`) 
									JOIN `smartukm_kommune` AS `k` ON (`k`.`id` = `rel`.`k_id`) 
									WHERE `k`.`idfylke` = '#fylke' 
									AND `place`.`season` = '#season'
									) 
								AS `temp`
								  ",
								 array('fylke'=>$this->info['fylke_id'],'season'=>$select_season));

				$personer = (int)$ureg->run('field','ureg');
			
				$qry = new SQL("SELECT COUNT(`s_id`) AS `personer`
								FROM `".$table."`
								WHERE `f_id` = '#fylke'
								AND `p_id` != '0'
								AND `status` = '8'
								AND `season` = '#season'"
								.($today_selected_year ? "AND `s_id` < '#oldtoday'" : ''),
								 array('fylke'=>$this->info['fylke_id'],'season'=>$select_season, 'oldtoday'=> $oldtoday ));

				$personer += (int)$qry->run('field','personer');
			
				$qry = new SQL("SELECT COUNT(`s_id`) AS `innslag`
								FROM `".$table."`
								WHERE `f_id` = '#fylke'
								AND `p_id` = '0'
								AND `status` = '8'
								AND `season` = '#season'"
								.($today_selected_year ? "AND `s_id` < '#oldtoday'" : ''),
								 array('fylke'=>$this->info['fylke_id'],'season'=>$select_season, 'oldtoday'=> $oldtoday ));
				$innslag += (int)$qry->run('field','innslag');
			}
			return array('innslag'=>$innslag,'personer'=>$personer);
		}
	
		##########################################################################################
		##					FUNKSJONER RELATERT TIL MØNSTRINGENS FORESTILLINGER					##
		##########################################################################################
		########################################  PRIVATE  #######################################


		########################################   PUBLIC  #######################################
		public function ambassadorer() {
			if(isset($this->ambassadorer)) {
				return $this->ambassadorer;
			}
			require_once('UKM/ambassador.class.php');
			$this->ambassadorer = array();

			if($this->get('type')=='kommune') {
				$kommuner = new SQL("SELECT `k_id`
									FROM `smartukm_rel_pl_k`
									WHERE `pl_id` = '#plid'
									AND `season` = '#season'",
									array('plid'=>$this->get('pl_id'),
										  'season'=>$this->get('season')));
				$kommuner = $kommuner->run();
				while($r = mysql_fetch_assoc($kommuner)) {
					$kommunearray[] = $r['k_id'];
				}
				
				$kommuner = new SQL("SELECT `pl_id`
									 FROM `smartukm_rel_pl_k`
									 WHERE `k_id` IN ('".implode("','",$kommunearray)."')");
				$kommuner = $kommuner->run();
				while($r = mysql_fetch_assoc($kommuner)) {
					$plarray[] = $r['pl_id'];
				}
				$pl_ids = "'".implode("','",$plarray)."'";
			} else {
				$kommuner = new SQL("SELECT `id` FROM `smartukm_kommune`
									 WHERE `idfylke`='#fylke'",
									 array('fylke'=>$this->get('fylke_id')));
				$kommuner = $kommuner->run();
				$kommunearray = array();
				while($r = mysql_fetch_assoc($kommuner)) {
					$kommunearray[] = $r['id'];
				}
				$kommuner = new SQL("SELECT `pl_id`
									 FROM `smartukm_rel_pl_k`
									 WHERE `k_id` IN ('".implode("','",$kommunearray)."')");
				$kommuner = $kommuner->run();
				$plarray = array();
				while($r = mysql_fetch_assoc($kommuner)) {
					$plarray[] = $r['pl_id'];
				}
//				$plarray[] = get_option('pl_id');
				$pl_ids = "'".implode("','",$plarray)."'";
			}
			$qry = new SQL("SELECT * FROM `ukm_ambassador`
						    WHERE `pl_id` IN (".$pl_ids.")
						    AND `deleted` = 'false'
						    ORDER BY `amb_firstname`,
							`amb_lastname` ASC"
							);
			$res = $qry->run();
			while($r = mysql_fetch_assoc($res)) {
				$this->ambassadorer[] = new ambassador($r['amb_faceID']);
			}
			return $this->ambassadorer;
		}
		
		############################################
		## Henter ut informasjon om en spesifikk forestilling
		############################################
		public function har_program() {
			$concertsSql = new SQL( 'SELECT `c_id`
							    	 FROM `smartukm_concert` 
							    	 WHERE `pl_id` = ' . $this->info['pl_id'] . '
							    	 LIMIT 1 ' );
			$res = $concertsSql->run();
			return mysql_num_rows( $res ) > 0;
		}
		
		public function forestilling( $c_id ){
			return $this->concert( $c_id );
		}
		public function concert( $c_id ) {
			/* Henter ut en konsert */
			$concertSql = new SQL( 'SELECT `c_id`, `c_name`, `c_start`, `c_place` 
								    FROM `smartukm_concert` WHERE `c_id` = ' . $c_id );
								    		  
			$ret = $concertSql->run( 'array' );

			$ret['c_name'] = utf8_encode($ret['c_name']);
			$ret['c_place'] = utf8_encode($ret['c_place']);
			
			return $ret;
		}
		
		############################################
		## Henter ut informasjon om alle forestillinger med samme pl_id
		############################################
		public function forestillinger($order = 'c_start', $filter = true) {
			return $this->concerts($order, $filter);
		}
		public function concerts( $order = 'c_start', $filter = true ) {
			/** 
			 * 
			 * OBS OBS OBS
			 * 
			 *
			 * Funksjonen kopiert over til V2 - endringer her må gjenspeiles i V2
			 *
			 *
			**/
			/* Henter ut konserter */
			if( $filter === true )
				$concertsSql = new SQL( 'SELECT `c_id`, `c_name`, `c_start`, `c_place`, `c_visible_detail` 
								    	 FROM `smartukm_concert` 
								    	 WHERE `c_visible_program` = true AND `pl_id` = ' . $this->info['pl_id'] . '
								    	 ORDER BY ' . $order . ' ASC' );
			else
				$concertsSql = new SQL( 'SELECT `c_id`, `c_name`, `c_start`, `c_place`, `c_visible_detail`
								    	 FROM `smartukm_concert` 
								    	 WHERE `pl_id` = ' . $this->info['pl_id'] . '
								    	 ORDER BY ' . $order . ' ASC' );
			$concertsResult = $concertsSql->run();
			
			$concertsRows = array();
			
			while( $concertsRow = mysql_fetch_assoc($concertsResult) ) {
				$concertsRow['c_name'] = utf8_encode($concertsRow['c_name']);
				$concertsRow['c_place'] = utf8_encode($concertsRow['c_place']);
				$concertsRows[] = $concertsRow;
			}
			
			return $concertsRows;
		}
	
		############################################
		## Henter ut ID til alle band som tilhører en forestilling
		## @return Array med innslagsID'er med ORDER
		############################################

		## !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
		## OBS: BURDE BRUKE FORESTILLINGSOBJEKT !!!!!!!!
		## !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

		public function concertBands( $c_id ) {
			$bandSql = new SQL( 'SELECT `b_id`, `order` 
								 FROM `smartukm_rel_b_c` 
								 WHERE `c_id` = ' . $c_id . '
								 ORDER BY `order` ASC');
			$bandResult = $bandSql->run();
			$bandRows = array();
			
			while( $bandRow = mysql_fetch_assoc( $bandResult ) )
				$bandRows[] = $bandRow;
				
			return $bandRows;
		}
		
			
		##########################################################################################
		##				FUNKSJONER RELATERT TIL MØNSTRINGENS KONTAKTPERSONER					##
		##########################################################################################
		########################################  PRIVATE  #######################################

		########################################   PUBLIC  #######################################
			
		############################################
		## Gir en ID-liste over kontaktpersoner i mønstringen
		## @return Array med kontaktobjekter
		############################################
		public function kontakter() {
			$liste = array();
			require_once('UKM/kontakt.class.php');
			$sql = new SQL("SELECT `ab_id` AS `id`, `pl_ab_id`
							FROM `smartukm_rel_pl_ab`
							WHERE `pl_id` = '#plid'
							ORDER BY `order` ASC",
							array('plid'=>$this->info['pl_id']));
			$res = $sql->run();
			while($r = mysql_fetch_assoc($res))
				$liste[$r['id']] = new kontakt($r['id'], $r['pl_ab_id']);

			return $liste;
		}

		############################################
		## Gir en ID-liste over kontaktpersoner i mønstringen
		## @return Array med kontaktobjekter
		############################################
		public function kontakter_pamelding() {
			require_once('UKM/kontakt.class.php');
//			UKM_loader('pamelding/contact');
			if($this->info['type']=='kommune') {
				foreach($this->info['kommuner'] as $k_id => $kommune) {
					$contacts[$kommune['name']] = $this->hovedkontakt($kommune['id'], true);
				}
			} else {
				$contacts[] = $this->hovedkontakt(0, true);
			}
			return $contacts;
		}
		
		public function hovedkontakt($kommune_id, $object=false) {
			require_once('UKM/kontakt.class.php');
			## FELLESMØNSTRING
			if($this->fellesmonstring()) {
				$pl_contact = "SELECT `smartukm_contacts`.`id`, `name`,`tlf`,`email`,`picture`,`facebook`
							FROM `smartukm_contacts`
							JOIN `smartukm_rel_pl_ab` ON (`smartukm_contacts`.`id` = `smartukm_rel_pl_ab`.`ab_id`)
							WHERE `smartukm_rel_pl_ab`.`pl_id` = '#pl_id'
							AND `smartukm_contacts`.`kommune` = '#kommune'
							ORDER BY `order` ASC
							LIMIT 1";
				$pl_contact = new SQL($pl_contact, array('pl_id'=>$this->info['pl_id'], 'kommune'=>$kommune_id));
				$pl_contact = $pl_contact->run();
				
				#!#!# FELLESMØNSTRING, FOUND TOP CONTACT , NOT ASSOCIATED TO KOMMUNE
				if(mysql_num_rows($pl_contact) != 1) {
					$pl_contact = "SELECT `smartukm_contacts`.`id`, `name`,`tlf`,`email`,`picture`,`facebook`
								FROM `smartukm_contacts`
								JOIN `smartukm_rel_pl_ab` ON (`smartukm_contacts`.`id` = `smartukm_rel_pl_ab`.`ab_id`)
								WHERE `smartukm_rel_pl_ab`.`pl_id` = '#pl_id'
								ORDER BY `order` ASC
								LIMIT 1";
					$pl_contact = new SQL($pl_contact, array('pl_id'=>$this->info['pl_id']));
					$pl_contact = $pl_contact->run('array');
				
				#!#!# FELLESMØNSTRING, FOUND CONTACT FOR GIVEN KOMMUNE
				} else {
					$pl_contact = mysql_fetch_assoc($pl_contact);	
				}				
			#!# ENKELMØNSTRING, FETCH TOP ONE
			} else {
				$pl_contact = "SELECT `smartukm_contacts`.`id`, `name`,`tlf`,`email`,`picture`,`facebook`
							FROM `smartukm_contacts`
							JOIN `smartukm_rel_pl_ab` ON (`smartukm_contacts`.`id` = `smartukm_rel_pl_ab`.`ab_id`)
							WHERE `smartukm_rel_pl_ab`.`pl_id` = '#pl_id'
							ORDER BY `order` ASC
							LIMIT 1";
				$pl_contact = new SQL($pl_contact, array('pl_id'=>$this->info['pl_id']));
				$pl_contact = $pl_contact->run('array');
			}

			return $object ? new kontakt($pl_contact['id']) : $pl_contact['id'];
		}
		##########################################################################################
		##						  FUNKSJONER RELATERT TIL VIDERESENDING							##
		##########################################################################################
		########################################  PRIVATE  #######################################

		############################################
		## Henter ut alle videresendte innslag fra denne mønstringen
		## lagrer innslag i arrayet videresendte
		############################################
		private function _videresendte() {
			$this->videresendte = array();
			$bands = $this->_load_innslag_loop($k_id['id'],8,false,true);
			for($i=0; $i<sizeof($bands); $i++) {
				$set = $bands[$i];
				$set['b_name'] = utf8_encode($set['b_name']);
				$infos = array('b_id'=>$set['b_id'],
							   'b_status'=>$set['b_status'],
							   'bt_id'=>$set['bt_id'],
							   'b_name'=>$set['b_name']);
				$this->videresendte[] = $infos;						
			}
		}

		########################################   PUBLIC  #######################################

		############################################
		## Henter ut alle videresendte innslag fra denne mønstringen
		## @return array med innslag
		############################################
		public function videresendte() {
			if(!isset($this->videresendte))
				$this->_videresendte();
			return $this->videresendte;
		}


		##########################################################################################
		##							FUNKSJONER RELATERT TIL MØNSTRINGEN							##
		##########################################################################################
		########################################  PRIVATE  #######################################
		private function _fellesmonstring(){
			$this->info['fellesmonstring'] = false;
			if($this->info['type']=='kommune' && isset($this->info['kommuner']) && sizeof($this->info['kommuner']) > 1)
				$this->info['fellesmonstring'] = true;
		}
		
		public function fellesmonstring() {
			$this->_fellesmonstring();
			return $this->info['fellesmonstring'];
		}


		########################################   PUBLIC  #######################################
		
		public function videresendTil($plobject=false) {
			if($plobject && $this->info['type']=='land')
				return $this;
			if($plobject && $this->info['type']=='fylke')
				return $this->hent_landsmonstring();
			if($plobject)
				return $this->hent_fylkesmonstring();
			if($this->info['type']=='fylke')
				return $this->hent_landsmonstring()->g('pl_id');
			if($this->info['type']=='land')
				return $this->g('pl_id');
			return $this->hent_fylkesmonstring()->g('pl_id');
		}
		
		############################################
		## Hent ut fylkesmønstringsobjektet for en lokalmønstring
		## OBSOBS, kan også kjøres for fylkesmønstring, og det gir nok ikke ønsket resultat..
		## @return Object monstring
		############################################
		public function hent_fylkesmonstring() {
			$sql = new SQL("SELECT `pl_id`
							FROM `smartukm_place`
							WHERE `pl_fylke` = '#fylke'
							AND `season` = '#season'",
							array('fylke'=>$this->info['fylke_id'],'season'=>get_option('season')));
			$fylkePL = $sql->run('field','pl_id');
			if(is_numeric($fylkePL))
				return new monstring($fylkePL);
			return false;
		}	

		############################################
		## Hent ut fylkesmønstringsobjektet for en lokalmønstring
		## @return Object monstring
		############################################
		public function hent_landsmonstring() {
			$sql = new SQL("SELECT `pl_id`
							FROM `smartukm_place`
							WHERE `pl_fylke` = '123456789'
							AND `pl_kommune` = '123456789'
							AND `season` = '#season'",
							array('season'=>get_option('season')));
			$landPL = $sql->run('field','pl_id');
			if(is_numeric($landPL))
				return new monstring($landPL);
			return false;
		}
		
		public function hent_lokalmonstringer() {
			if(!$this->info['type'] == 'fylke')
				return false;
				
			$plids = array();
			$sql = new SQL("SELECT `rel`.`pl_id`
							FROM `smartukm_kommune` AS `k`
							JOIN `smartukm_rel_pl_k` AS `rel` ON (`rel`.`k_id` = `k`.`id`)
							JOIN `smartukm_place` AS `pl` ON (`pl`.`pl_id` = `rel`.`pl_id`)
							WHERE `k`.`idfylke` = '#f_id'
							AND `rel`.`season` = '#season'
							ORDER BY `pl`.`pl_name` ASC",
							array('f_id' => $this->info['pl_fylke'],
								  'season' => $this->info['season'] ));
			#echo $sql->debug();
			$res = $sql->run();
			if($res)
				while( $r = mysql_fetch_assoc( $res ) ) {
					$plids[] = $r['pl_id'];
				}
			
			return $plids;
		}
		
		
		public function data_videresendingsskjema() {
			$svar = array();
			$sql = new SQL("SELECT * FROM `smartukm_videresending_fylke_svar`
							WHERE `pl_id` = '#plid'",
							array('plid' => $this->info['pl_id']));
			$res = $sql->run();
			if($res)
				while( $r = mysql_fetch_assoc( $res ) ) {
					$svar[$r['q_id']] = utf8_encode($r['answer']);
				}
			return $svar;
		}
		
		private function _link() {
			$this->_url();
			$this->info['link'] = '//'.(defined('UKM_HOSTNAME')?UKM_HOSTNAME:'ukm.no').'/'. $this->url.'/';
			$this->link = $this->info['link'];
/*
			if($this->get('type') == 'kommune')
				$this->info['link'] = 'http://'.UKM_HOSTNAME.'/pl'.$this->get('pl_id').'/';
			elseif($this->get('type') == 'land')
				$this->info['link'] = 'http://'.UKM_HOSTNAME.'/festivalen/';
			else {
				$fylkename = $this->_sanitize_nordic( $this->get('fylke_name') );
				$this->info['link'] = 'http://'.UKM_HOSTNAME.'/'. $fylkename .'/';
			}
*/
		}
		
		private function _url() {
			if($this->get('type') == 'fylke') {
				#$this->url = $this->_sanitize_nordic($this->get('fylke_name'));
				switch( $this->info['pl_fylke'] ) {
					case 1:		$this->url = 'ostfold';			break;
					case 2:		$this->url = 'akershus';		break;
					case 3:		$this->url = 'oslo';			break;
					case 4:		$this->url = 'hedmark';			break;
					case 5:		$this->url = 'oppland';			break;
					case 6:		$this->url = 'buskerud';		break;
					case 7:		$this->url = 'vestfold';		break;
					case 8:		$this->url = 'telemark';		break;
					case 9:		$this->url = 'aust-agder';		break;
					case 10:	$this->url = 'vest-agder';		break;
					case 11:	$this->url = 'rogaland';		break;
					case 12:	$this->url = 'hordaland';		break;
					case 14:	$this->url = 'sognogfjordane';	break;
					case 15:	$this->url = 'moreogromsdal';	break;
					case 16:	$this->url = 'sor-trondelag';	break;
					case 17:	$this->url = 'nord-trondelag';	break;
					case 18:	$this->url = 'nordland';		break;
					case 19:	$this->url = 'troms';			break;
					case 20:	$this->url = 'finnmark';		break;
					case 21:	$this->url = 'testfylke';		break;
					case 30:	$this->url = 'troms';			break;
					case 32:	$this->url = 'gjester';			break;
				}
			} elseif($this->get('type')=='land')
				$this->url = 'festivalen';
			else
				$this->url = 'pl'. $this->get('pl_id');
			$this->info['url'] = $this->url;
		}
		
		public function _sanitize_nordic($text) {
			// Remove tags and make lowercase
			$text = strip_tags( strtolower($text) );
			
			// Replace nordic with a,o,a
			$text = str_replace(array('Æ','Ø','Å','æ','ø','å','&aelig;','&oslash;','&aring'),
								array('a','o','a','a','o','a','a','o','a'),
								$text);
			// Remove html entities
			$text = preg_replace('/&#?[a-z0-9]{2,8};/i', '', $text);
			
			// Return only a-z-
			return preg_replace('/[^a-z-]+/','', $text );
		}
		
		public function _SMAS_encoding($content) {
			$characterEncoding = mb_detect_encoding($content."a", 'UTF-8, UTF-16, ISO-8859-1, ISO-8859-15, Windows-1252, ASCII');
			switch ($characterEncoding) {
			 case "UTF-8":
			   return utf8_decode($content);
			 case "ISO-8859-1":
			   break;
			 default:
			   return mb_convert_encoding($content,$characterEncoding);
			}
			return $content;	
		}
		
		public function har_ukmtv() {
        	$sql = new SQL("SELECT `tv_id` FROM `ukm_tv_files`
        					WHERE `tv_tags` LIKE '%|pl_#plid|%'
        					LIMIT 1",
        					array('plid' => $this->g('pl_id')) );
        	$res = $sql->run();
        	return mysql_num_rows( $res ) > 0;
		}
}

class monstring_v2 {
	var $id = null;
	var $type = null;
	var $navn = null;
	var $start = null;
	var $start_datetime = null;
	var $stop = null;
	var $stop_datetime = null;
	var $frist_1 = null;
	var $frist_1_datetime = null;
	var $frist_2 = null;
	var $frist_2_datetime = null;
	var $program = null;
	var $kommuner_id = null;
	var $kommuner = null;
	var $sesong = null;
	var $innslag = null;
	
	var $attributes = null;
	
	/**
	 * getLoadQry
	 * Brukes for å få standardiserte databaserader inn for 
	 * generering via _load_by_row
	 *
	 * WHERE-selector og evt ekstra joins må legges på manuelt
	**/
	static function getLoadQry() {
		return "SELECT `place`.*,
					GROUP_CONCAT(`kommuner`.`k_id`) AS `k_ids`
				FROM `smartukm_place` AS `place`
				LEFT JOIN `smartukm_rel_pl_k` AS `kommuner`
					ON (`kommuner`.`pl_id` = `place`.`pl_id`)
				";
	}
	
	public function __construct( $id_or_row ) {

		if( is_numeric( $id_or_row ) ) {
			$this->_load_by_id( $id_or_row );
		} elseif( is_array( $id_or_row ) ) {
			$this->_load_by_row( $id_or_row );
		} else {
			throw new Exception('MONSTRING: Oppretting av objekt krever numerisk id eller databaserad');
		}
		
		$this->attributes = array();	
	}		
	
	private function _load_by_id( $id ) {
		$qry = new SQL( self::getLoadQry() . "WHERE `place`.`pl_id` = '#plid'",
					array('plid' => $id)
					);
		$res = $qry->run('array');
		
		$this->_load_by_row( $res );
	}
	
	private function _load_by_row( $row ) {
		if( !is_array( $row ) ) {
			throw new Exception('MONSTRING: _load_by_row krever dataarray!');
		}
		// Beregn type
		if( 0 == $row['pl_fylke'] ) {
			$this->setType('kommune');
		} elseif( 123456789 == $row['pl_fylke'] ) {
			$this->setType('land');
		} else {
			$this->setType('fylke');
		}
		
		
		// Sett opp fylkesmønstringen
		if( 'fylke' == $this->getType() ) {
			$this->setFylke( $row['pl_fylke'] );
		} elseif( 'land' == $this->getType() ) {
			
		} else {
			$this->setKommuner( explode(',', $row['k_ids'] ) );
		}
		$this->setId( $row['pl_id'] );
		$this->setNavn( utf8_encode($row['pl_name']) );
		$this->setStart( $row['pl_start'] );
		$this->setStop( $row['pl_stop'] );
		$this->setFrist1( $row['pl_deadline'] );
		$this->setFrist2( $row['pl_deadline2'] );
		$this->setSesong( $row['season'] );
	}
	
	
	/**
	 * Sett attributt
	 * Sett egenskaper som for enkelhets skyld kan følge mønstringen et lite stykke
	 * Vil aldri kunne lagres
	 *
	 * @param string $key
	 * @param $value
	 *
	 * @return innslag
	**/
	public function setAttr( $key, $value ) {
		$this->attributes[ $key ] = $value;
		return $this;
	}
	
	/**
	 * Hent attributt
	 *
	 * @param string $key
	 *
	 * @return value
	**/
	public function getAttr( $key ) {
		return isset( $this->attributes[ $key ] ) ? $this->attributes[ $key ] : false;
	}
		
	/**
	 * Sett ID
	 *
	 * @param integer id 
	 *
	 * @return $this
	**/
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	/**
	 * hent ID
	 * @return integer $id
	**/
	public function getId() {
		return $this->id;
	}		
	
	
	/**
	 * Sett type
	 *
	 * @param integer $type
	 *
	 * @return $this;
	**/
	public function setType( $type ) {
		$this->type = $type;
		return $this;
	}
	/**
	 * Hent type
	 *
	 * @return innslag_type $type
	**/
	public function getType( ) {
		return $this->type;
	}
	
	/**
	 * Sett navn
	 *
	 * @param string $navn
	 *
	 * @return $this
	**/
	public function setNavn( $navn ) {
		$this->navn = $navn;
		return $this;
	}
	/**
	 * hent navn
	 * @return string $navn
	**/
	public function getNavn() {
		return $this->navn;
	}
	
	/**
	 * erRegistrert
	 *
	 * @return bool
	**/
	public function erRegistrert() {
		return $this->start > 0;
	}

	/**
	 * Sett start-tidspunkt
	 *
	 * @param unixtime $start
	 * @return $this
	**/
	public function setStart( $unixtime ) {
		$this->start = $unixtime;
		return $this;
	}
	/**
	 * Hent start-tidspunkt
	 *
	 * @return DateTime $start
	**/
	public function getStart() {
		if( null == $this->start_datetime ) {
			$this->start_datetime = new DateTime();
			$this->start_datetime->setTimestamp( $this->start );
		}
		return $this->start_datetime;
	}
	
	/**
	 * Sett stopp-tidspunkt
	 *
	 * @param unixtime $stop
	 * @return $this
	**/
	public function setStop( $unixtime ) {
		$this->stop = $unixtime;
		return $this;
	}
	/**
	 * Hent stopp-tidspunkt
	 *
	 * @return DateTime $stop
	**/
	public function getStop() {
		if( null == $this->stop_datetime ) {
			$this->stop_datetime = new DateTime();
			$this->stop_datetime->setTimestamp( $this->stop );
		}
		return $this->stop_datetime;
	}
	
	/**
	 * Sett frist 1-tidspunkt
	 *
	 * @param unixtime $frist1
	 * @return $this
	**/
	public function setFrist1( $unixtime ) {
		$this->frist_1 = $unixtime;
		return $this;
	}
	/**
	 * Hent frist 1-tidspunkt
	 *
	 * @return DateTime $frist1
	**/
	public function getFrist1() {
		if( null == $this->frist_1_datetime ) {
			$this->frist_1_datetime = new DateTime();
			$this->frist_1_datetime->setTimestamp( $this->frist_1 );
		}
		return $this->frist_1_datetime;
	}	 
	/**
	 * Sett frist 2-tidspunkt
	 *
	 * @param unixtime $frist2
	 * @return $this
	**/
	public function setFrist2( $unixtime ) {
		$this->frist_2 = $unixtime;
		return $this;
	}
	/**
	 * Hent frist 2-tidspunkt
	 *
	 * @return DateTime $frist2
	**/
	public function getFrist2() {
		if( null == $this->frist_2_datetime ) {
			$this->frist_2_datetime = new DateTime();
			$this->frist_2_datetime->setTimestamp( $this->frist_2 );
		}
		return $this->frist_2_datetime;
	}
	
	
	/**
	 * Er dette en singelmønstring (altså ikke fellesmønstring
	 *
	 * return bool
	**/
	public function erSingelmonstring() {
		return 1 == sizeof( $this->kommuner_id );
	}
	/**
	 * Er dette en fellesmønstring 
	 *
	**/
	public function erFellesmonstring() {
		return 1 < sizeof( $this->kommuner_id );
	}

	
	/**
	 * Sett kommuner
	 *
	 * @param array $kommuner_id
	 * @return $this
	**/
	public function setKommuner( $kommuner_id ) {
		$this->kommuner_id = $kommuner_id;
		return $this;
	}
	
	/**
	 * Hent kommune
	 *
	 * @return object $kommune
	**/
	public function getKommune() {
		if( !$this->erSingelmonstring() ) {
			throw new Exception('MONSTRING_V2: Kan ikke bruke getKommune på mønstringer med flere kommuner');
		}
		if( null == $this->kommune ) {
			$this->kommune = new kommune( $this->kommune_id );
		}
		return $this->kommune;
	}
	
	/**
	 * Hent alle kommuner for en mønstring
	 *
	**/
	public function getKommuner() {
		require_once('UKM/kommuner.collection.php');
		
		if( null == $this->kommuner ) {
			if( 'kommune' == $this->getType() ) {
				$this->kommuner = new kommuner();
				foreach( $this->kommuner_id as $id ) {
					$this->kommuner->add( new kommune( $id ) );
				}
			} elseif( 'fylke' == $this->getType() ) {
				$this->kommuner = $this->getFylke()->getKommuner();
			}
		}
		return $this->kommuner;
	}

	/**
	 * Sett fylkeID
	 *
	 * @param int $fylke_id
	 * @return $this
	 * 
	**/
	public function setFylke( $fylke_id ) {
		$this->fylke_id = $fylke_id;
		return $this;
	}
	
	/**
	 * Sett sesong
	 *
	 * @param int $seson
	 * @return $this
	**/
	public function setSesong( $sesong ) {
		$this->sesong = $sesong;
		return $this;
	}
	/**
	 * Hent sesong
	 *
	 * @return int $sesong
	**/
	public function getSesong() {
		return $this->sesong;
	}
	
	/**
	 * Hent fylke
	 *
	 * @return fylke
	**/
	public function getFylke() {
		if( null == $this->fylke ) {
			if( null == $this->fylke_id && 'kommune' == $this->getType() ) {
				$first_kommune = $this->getKommuner()->first();
				$this->setFylke( $first_kommune->getFylke()->getId() );
			}
			$this->fylke = fylker::getById( $this->fylke_id );
		}
		return $this->fylke;
	}
	
	/**
	 * Hent program for gitt mønstring
	 *
	 * @return array forestilling.class
	 *
	**/
	public function getProgram() {
		if( null !== $this->program ) {
			return $this->program;
		}
		require_once('UKM/forestillinger.collection.php');

		$this->program = new forestillinger( 'monstring', $this->getId() );

		return $this->program;
	}
	
	/**
	 * Hent innslag påmeldt mønstringen
	 *
	 * @return innslag collection
	**/
	public function getInnslag() {
		if( null == $this->innslag ) {
			$this->innslag = new innslag_collection( 'monstring', $this->getId() );
			if( 'land' == $this->getType() ) {
				$this->innslag->setContainerDataMonstring( $this->getId(), $this->getType(), $this->getSesong(), false, false );
			} else {
				$this->innslag->setContainerDataMonstring( $this->getId(), $this->getType(), $this->getSesong(), $this->getFylke()->getId(), $this->getKommuner()->getIdArray() );
			}
		}
		return $this->innslag;
	}
	
	/**
	 * Hent lenke for mønstringen
	 *
	 * @return string url
	**/
	public function getLink() {
		return '//'. UKM_HOSTNAME .'/'. $this->getPath() .'/';
	}
	
	/**
	 * Hent relativ path for mønstringen
	 *
	 * @return string relativ path til domenet
	**/
	public function getPath() {
		if( 'fylke' == $this->getType() ) {
			return $this->getFylke()->getLink();
		} elseif( 'land' == $this->getType() ) {
			return 'festivalen';
		}
		return 'pl'. $this->getId();		
	}
	
	/**
	 * Hent hvilke innslagstyper som kan være påmeldt denne møsntringen
	 *
	 * @return Collection innslagstyper 
	**/
	public function getInnslagTyper() {
		if( null == $this->innslagTyper ) {
			$this->innslagTyper = new innslag_typer();
			$sql = new SQL("SELECT `bt_id`
							FROM `smartukm_rel_pl_bt`
							WHERE `pl_id` = '#pl_id'
							ORDER BY `bt_id` ASC",
						   array('pl_id'=> $this->getId() )
						  );
			$res = $sql->run();
			while( $r = mysql_fetch_assoc( $res ) ) {
				$this->innslagTyper->addById( $r['bt_id'] );
			}
		}
		return $this->innslagTyper;
	}
}
?>
