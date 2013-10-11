<?php
require_once('UKM/person.class.php');
require_once('UKM/inc/ukmlog.inc.php');
function create_innslag($bt_id, $season, $pl_id, $kommune, $contact=false){
	$tittellos = in_array($bt_id, array(4,5,8,9,10));
	
#	if($tittellos && !$contact)
		
	

	$band = new SQLins('smartukm_band');
	$band->add('b_season', $season);
	$band->add('b_status', 8);
	$band->add('b_name', 'Nytt innslag');
	$band->add('b_kommune', $kommune);
	$band->add('b_year', date('Y'));
	$band->add('b_subscr_time', time());
	$band->add('bt_id', $bt_id);
	
	if(is_object($contact))
		$band->add('b_contact', $contact->g('p_id'));

#	echo $band->debug();
	$bandres = $band->run();
	$b_id = $band->insid();
	
	$tech = new SQLins('smartukm_technical');
	$tech->add('b_id', $b_id);
	$tech->add('pl_id', $pl_id);
	$techres = $tech->run();
	
	$rel = new SQLins('smartukm_rel_pl_b');
	$rel->add('pl_id', $pl_id);
	$rel->add('b_id', $b_id);
	$rel->add('season', $season);
	$relres = $rel->run();
	
	return $b_id;
}

class innslag {
	## Attributtkontainer
	var $info = array();
	var $personer_loaded = false;
	var $items_loaded = false;
	var $personer = array();
	var $items = array();
	
	
	public function update($field, $post_key=false, $force = false) {
		if(!$post_key)
			$post_key = $field;
		if(!$force && $_POST[$post_key] == $_POST['log_current_value_'.$post_key])
			return true;
			
		// Tekniske krav skal i en annen tabell enn resten
		if (in_array($field, array('td_demand', 'td_konferansier'))) {
			$qry = new SQLins('smartukm_technical', array('b_id'=>$this->info['b_id']));
			if (!$force)
				UKMlog('smartukm_technical',$field,$post_key,$this->info['b_id']);
		}
		// Alt annet
		else {
			$qry = new SQLins('smartukm_band', array('b_id'=>$this->info['b_id']));
			if (!$force)
				UKMlog('smartukm_band',$field,$post_key,$this->info['b_id']);
		}
			
		$qry->add($field, $_POST[$post_key]);
		$this->info[$field] = $_POST[$post_key];
		return $qry->run();
	}
	
	public function clear($field, $post_key=false) {
		if(!$post_key)
			$post_key = $field;
					
		$this->update($field, '', true);
	}
	
	public function addPerson($p_id) {
		$qry = new SQLins('smartukm_rel_b_p');
		$qry->add('b_id', $this->info['b_id']);
		$qry->add('p_id', $p_id);
		$qry->add('season', $this->g('b_season'));
		$qry->add('b_p_year', $this->g('b_season'));
		$res = $qry->run();
		
#		UKM_loader('private');
#		if(UKM_private()){
		if($this->info['b_contact'] == 0) {
			$_POST['autofix_b_contact'] = $p_id;
			$_POST['log_current_value_autofix_b_contact'] = 0;
			$this->update('b_contact', 'autofix_b_contact');	
		}
#		}
	}
	
	public function removePerson($p_id) {
		$qry = new SQLdel('smartukm_rel_b_p', 
							array('b_id'=>$this->info['b_id'], 
								  'p_id'=>$p_id, 
								  'season'=>$this->g('b_season'), 
								  'b_p_year'=>$this->g('b_season')));
		return $qry->run();
	}
	
	public function delete() {		
		$qry = new SQLins('smartukm_band', array('b_id'=>$this->g('b_id')));
		$qry->add('b_status', 99);
		$res = $qry->run();
		#echo $qry->debug();
		
		$_POST['b_status'] = 99;
		UKMlog('smartukm_band','b_status','b_status',$this->g('b_id'));
		
		return ($res===1);
	}
	
	## Henter et innslags innebygde attributter fra b_id
	public function innslag($b_id, $onlyifsubscribed=true) {
		$qry = "SELECT `smartukm_band`.*, 
					   `smartukm_band_type`.`bt_name`, 
					   `smartukm_band_type`.`bt_form`,
					   `td`.`td_demand`,
					   `td`.`td_konferansier`
				FROM `smartukm_band`
				LEFT JOIN `smartukm_band_type` ON (`smartukm_band_type`.`bt_id` = `smartukm_band`.`bt_id`)
				LEFT JOIN `smartukm_technical` AS `td` ON (`td`.`b_id` = `smartukm_band`.`b_id`)
				WHERE `smartukm_band`.`b_id` = '".$b_id."' "
			 .  ($onlyifsubscribed ? "AND `smartukm_band`.`b_status` = 8" : '')
				;
		$sql = new SQL($qry);
		$res = $sql->run('array');
		#$res = $wpdb->get_row($qry,'ARRAY_A');
		
		$this->info = $res;
		$this->b_id = $this->id = $this->info['b_id'];
		
		$this->_loadKategoriogsjanger();
		
		## Korrigerer innslagsnavnet hvis det skulle være noe galt
		$this->correctName();
		$this->__charset();
		
		$this->_time_status_8();
	}

	## Gi ny verdi (value) til attributten (key)
	## OBS: Lagrer ikke!
	public function set($key, $value){
		$this->info[$key] = $value;
	}
	
	## Returnerer verdien til attributten (key)
	public function g($key) {	return $this->get($key);	}
	public function get($key) {
		if(is_array($this->info[$key]))
			return $this->info[$key];
			
		return utf8_encode($this->info[$key]);	
	}
		
	## Returnerer hele objektet for var-dump
	public function info(){
		return $this->info;
	}
	
	## Henter bilde

	public function loadBTIMG() {
		switch($this->info['bt_id']) {
			case 1: 	$img = 'scene';				break;
			case 2: 	$img = 'video';				break;
			case 3: 	$img = 'utstilling';		break;
			case 4: 	$img = 'konferansier';		break;
			case 5: 	$img = 'nettredaksjon';		break;
			case 6: 	$img = 'matkultur';			break;
			case 8: 	$img = 'arrangor';			break;
			case 9: 	$img = 'sceneteknikk';		break;
			default: 	$img = 'annet';				break;
		}
		$this->info['btimgName'] = $img;
		$this->info['btimg'] = '<img src="http://ico.ukm.no/subscription/'.$img.'.png" style="border:none;" height="60" /><br />';
		$this->info['btimg_url'] = 'http://ico.ukm.no/subscription/'.$img.'.png';
	}
	
	public function loadGEO()
	{
		$qry = new SQL("SELECT `smartukm_kommune`.`name` AS `kommune`, 
					   `smartukm_kommune`.`id` AS `kommuneID`,
					   `smartukm_fylke`.`name` AS `fylke`, 
					   `smartukm_fylke`.`id` AS `fylkeID`
						FROM `smartukm_kommune`
						JOIN `smartukm_fylke` ON (`smartukm_fylke`.`id` = `smartukm_kommune`.`idfylke`)
						WHERE `smartukm_kommune`.`id` = '#id'",
						array('id'=>$this->info['b_kommune']));
		$res = $qry->run('array');
		$this->info['kommune_utf8'] = utf8_encode($res['kommune']);
		$this->info['kommune'] = ($res['kommune']);
		$this->info['kommuneID'] = $res['kommuneID'];
		$this->info['fylke_utf8'] = utf8_encode($res['fylke']);
		$this->info['fylke'] = ($res['fylke']);
		$this->info['fylkeID'] = $res['fylkeID'];
				 
	}
	
	private function _loadKategoriogsjanger() {
		$this->info['kategori'] = ($this->info['bt_id']==1 
									? ($this->info['b_kategori'] == 'scene' 
										? 'Musikk' 
										: ucfirst(utf8_decode($this->info['b_kategori']))
									  )
									: $this->info['bt_name']
								  );
		
		$sjanger  = !empty($this->info['b_sjanger']) ? $this->info['b_sjanger'] : '';
		$kategori = !empty($this->info['b_kategori']) ? ucfirst($this->info['b_kategori']) : '';
			
		if($kategori == $sjanger) $sjanger = '';
		if(!empty($sjanger) && !empty($kategori)) {
			$katOgSjan = $kategori . ' - ' . $sjanger;
		} elseif(!empty($sjanger)) {
			$katOgSjan = $sjanger;
		} elseif(!empty($kategori)) {
			$katOgSjan = $kategori;
		} else {
			$katOgSjan = '';	
		}
		if($this->tittellos()){
			$this->info['kategori_og_sjanger'] = $this->info['bt_name'];
			$this->info['b_kategori'] = $this->info['bt_name'];
		}
		else
			$this->info['kategori_og_sjanger'] = ($katOgSjan);
	}	
	
	private function __charset() {
		//$this->info['bt_name'] = utf8_encode($this->info['bt_name']);

		//$this->info['b_name'] = mb_detect_encoding($this->info['b_name'], "UTF-8") == "UTF-8" 
		//					 ? utf8_encode($this->info['b_name'])
		//					 : ($this->info['b_name']);
	}
	
	private function correctName() {
		if(empty($this->info['b_name']))
			$this->info['b_name'] = 'Innslag uten navn';
	}
	
	private function _time_status_8() {
		if(!in_array($this->info['bt_id'], array(1,2,3,6,7))) {
			$this->info['time_status_8'] = $this->info['b_subscr_time'];
			return;
		}

		$qry = new SQL("SELECT `log_time` FROM `ukmno_smartukm_log`
						WHERE `log_b_id` = '#bid'
						AND `log_code` = '22'
						ORDER BY `log_id` DESC",
						array('bid'=>$this->info['b_id']));
		$this->info['time_status_8'] = $qry->run('field','log_time');

		if(empty($this->info['time_status_8']))
			$this->info['time_status_8'] = $this->info['b_subscr_time'];
	}
	
	public function tittellos(){
		return !in_array($this->info['bt_id'], array(1,2,3,6,7));
	}
	
	####################################################################################
	## FUNKSJONER RELATERT TIL PERSONER I INNSLAGET
	####################################################################################
	## Returnerer en liste over innslags-ID'er. Hvis listen ikke er laget, last den inn
	public function kontaktperson() {
		if(!isset($this->kontaktperson))
			$this->kontaktperson = new person($this->g('b_contact'));
		return $this->kontaktperson;
	}
	
	public function setKontaktperson($p_id) {
		$qry = new SQLins('smartukm_band', array('b_id'=>$this->info['b_id']));
		$qry->add('b_contact', $p_id);
		$qry->run();
	}
	
	public function personer() {
		if(!$this->personer_loaded)
			$this->load_personer();
			
		return $this->personer;	
	}
	
	public function personObjekter() {
		if(!$this->personer_loaded)
			$this->load_personer();
		
		$persons = array();
		
		foreach( $this->personer as $person ) {
			$persons[] = new person($person['p_id'], $this->g('b_id'));
		}
		
		return $persons;
	}
	
	public function ikke_videresendte_personObjekter() {
		$this->ikke_videresendte_personer();
		$persons = array();
		foreach( $this->ikke_videresendte_personer as $person ) {
			$persons[] = new person($person['p_id'], $this->g('b_id'));
		}
		
		return $persons;
	}
	
	public function ikke_videresendte_personer() {
		if(!isset($this->ikke_videresendte_personer))
			$this->_load_ikke_videresendte_personer();
		return $this->ikke_videresendte_personer;
	}

	public function num_personer() {
		return sizeof($this->personer());
	}
	
	public function videresendte($pl_til) {
		$this->visKunVideresendte = $pl_til;
	}
	
	private function _load_ikke_videresendte_personer() {
		$this->ikke_videresendte_personer = array();
		$qry = $this->_load_personer_qry("LEFT JOIN `smartukm_fylkestep_p` AS `fs` ON (`fs`.`p_id` = `smartukm_participant`.`p_id`) "," AND `fs`.`b_id` IS NULL");
		$qry = new SQL($qry);
		$res = $qry->run();
		if($res&&mysql_num_rows($res)>0)
			while($set = mysql_fetch_assoc($res))
				$this->ikke_videresendte_personer[] = array('p_id'=>$set['p_id'], 
															'p_firstname'=>utf8_encode($set['p_firstname']), 
															'p_lastname'=>utf8_encode($set['p_lastname']), 
															'instrument'=>utf8_encode($set['instrument']), 
															'p_phone'=>$set['p_phone']);

	}
	
	private function _load_personer_qry($extraJoin='', $extraWhere='') {
		return "SELECT `smartukm_participant`.`p_id`, `p_firstname`, `p_lastname`, `instrument`, `p_phone` FROM `smartukm_participant`
				JOIN `smartukm_rel_b_p` ON (`smartukm_rel_b_p`.`p_id` = `smartukm_participant`.`p_id`)"
				.$extraJoin."
				WHERE `smartukm_rel_b_p`.`b_id` = ".$this->info['b_id']
				.$extraWhere."
				GROUP BY `smartukm_participant`.`p_id`
				ORDER BY `smartukm_participant`.`p_firstname`, `smartukm_participant`.`p_lastname` ASC";
	}
	
	public function load_personer() {
		if(isset($this->visKunVideresendte) 
		&& is_numeric($this->visKunVideresendte) 
		&& $this->info['bt_form'] != 'smartukm_titles_scene') {
			$extraJoin = 'JOIN `smartukm_fylkestep_p` AS `fs` 
							ON (`fs`.`p_id` = `smartukm_participant`.`p_id`) ';
			$extraWhere  = ' AND `fs`.`pl_id` = '.$this->visKunVideresendte.'';
		} else
			$extraJoin = $extraWhere = '';
		
		$qry = $this->_load_personer_qry($extraJoin, $extraWhere);
		
		$qry = new SQL($qry);
		$res = $qry->run();
		#$res = $wpdb->get_results($qry,'ARRAY_A');
		if($res&&mysql_num_rows($res)>0)
			while($set = mysql_fetch_assoc($res))
				$this->personer[] = array('p_id'=>$set['p_id'], 'p_firstname'=>utf8_encode($set['p_firstname']), 'p_lastname'=>utf8_encode($set['p_lastname']), 'instrument'=>utf8_encode($set['instrument']), 'p_phone'=>$set['p_phone']);
		
		$this->personer_loaded = true;
	}

	####################################################################################
	## FUNKSJONER RELATERT TIL RELATERTE ELEMENTER I INNSLAGET
	####################################################################################
	## Returnerer en liste over bilder, nyheter og videoer. Hvis listen ikke er laget, last den inn
	public function related_items() {
		if(!$this->items_loaded)
			$this->_load_related_items();
			
		return $this->items;	
	}

	private function _load_related_items() {
		require_once('UKM/related.class.php');
		$rel = new related($this->info['b_id']);
		$rel = $rel->get();
		if(is_array($rel))
			foreach($rel as $id => $info) {
				if($id == 0)
					$id = $info['rel_id'];
				$this->items[$info['post_type']][$id] = $info;
			}
		
		$this->_load_related_tv();
		$this->items_loaded = true;
	}
	
	private function _load_related_tv() {
		require_once('UKM/tv.class.php');
		require_once('UKM/tv_files.class.php');
		
		$tv_files = new tv_files('band', $this->id);
		while($tv = $tv_files->fetch()) {
			$this->items['tv'][$tv->id] = $tv;
		}
	}
	
	public function videresend($videresendFra, $videresendTil, $tittel = 0) {
		if ($videresendFra == 0 || $videresendTil == 0)
			return false;
			
		if (!is_numeric($tittel))
			$tittel = 0;			
		
		$season = new monstring($videresendFra);
		$season = $season->g('season');
			
		$test_relasjon = new SQL("SELECT * FROM `smartukm_rel_pl_b`
								  WHERE `pl_id` = '#plid'
								  AND `b_id` = '#bid'
								  AND `season` = '#season'",
								  array('plid'=>$videresendTil, 'bid'=>$this->g('b_id'), 'season'=>$season));
		$test_relasjon = $test_relasjon->run();	
		
		if(mysql_num_rows($test_relasjon)==0) {		
			$videresend_innslag_relasjon = new SQLins('smartukm_rel_pl_b');
			$videresend_innslag_relasjon->add('pl_id', $videresendTil);
			$videresend_innslag_relasjon->add('b_id', $this->g('b_id'));
			$videresend_innslag_relasjon->add('season', $season);
			$videresend_innslag_relasjon->run();
		}
		
		$test_fylkestep = new SQL("SELECT * FROM `smartukm_fylkestep`
									  WHERE `pl_id` = '#plid'
									  AND `pl_from` = '#pl_from'
									  AND `b_id` = '#bid'
									  AND `t_id` = '#t_id'",
									  array('plid'=>$videresendTil, 
									  		'bid'=>$this->g('b_id'),
											'pl_from'=>$videresendFra,
											't_id'=>$tittel));
		$test_fylkestep = $test_fylkestep->run();

		if (mysql_num_rows($test_fylkestep)==0) {
			$videresend_innslag = new SQLins('smartukm_fylkestep');
			$videresend_innslag->add('pl_id', $videresendTil);
			$videresend_innslag->add('pl_from', $videresendFra);
			$videresend_innslag->add('b_id', $this->g('b_id'));
			$videresend_innslag->add('t_id', $tittel);
			$videresend_innslag->run();
		}
		return true;
	}
	
	public function avmeld($videresendFra, $videresendTil, $tittel = 0, $slettRelasjoner = true) {
		if ($videresendFra == 0 || $videresendTil == 0)
			return false;
			
		if (!is_numeric($tittel))
			$tittel = 0;
		
		$monstring = new monstring($videresendFra);
		// DENNE BØR OPPDATERES, SLIK AT DEN ENDRER B_STATUS OG LOGGER VED AVMELDING P&aring; KOMMUNENIV&aring;!
		if ($monstring->g('pl_type') == 'kommune')
			return false;
		
		$season = $monstring->g('season');
			
		#if(!$slettedeRelasjoner || (is_array($slettedeRelasjoner) && !in_array($this->g('b_id'),$slettedeRelasjoner))) {
		if($slettRelasjoner) {
			$slett_relasjon = new SQLdel('smartukm_rel_pl_b',
								array('pl_id'=>$videresendTil,
									  'b_id'=>$this->g('b_id'),
									  'season'=>$season));
			$slett_relasjon->run();
			#$slettedeRelasjoner[] = $this->g('b_id');
		}
		
		$slett_relasjon = new SQLdel('smartukm_fylkestep',
					array('pl_id'=>$videresendTil,
						  'pl_from'=>$videresendFra,
						  'b_id'=>$this->g('b_id'),
						  't_id'=>$tittel));
		$slett_relasjon->run();				
#		return $slettedeRelasjoner;

		$this->statistikk_oppdater();
		return true;
	}

	
	public function bilde($width=120,$size='thumbnail',$wrap=true) {
		require_once('UKM/related.class.php');

		$rel = new related($this->info['b_id']);
		$img = $rel->getLastImage($size);
		if(!$img)		
			return $this->info['btimg'.($wrap?'':'_url')];
		if(!$wrap)
			return $img;
		return '<img src="'.$img.'" width="'.$width.'" />';
	}
	
	public function fjernfraforestilling($c_id) {
		if(!is_numeric($c_id)||!is_numeric($this->g('b_id'))||$c_id==0)
			return false;
		$qry = new SQLdel('smartukm_rel_b_c',
					array('b_id'=>$this->g('b_id'),
						  'c_id'=>$c_id));
		return $qry->run() == 1;
	}
	
	public function forestillinger($pl_id) {
		if(!isset($this->forestillinger))
			$this->_load_forestillinger($pl_id);
		return $this->forestillinger;
	}
	
	public function antall_hendelser($pl_id, $unntatt=array()){ return $this->antall_forestillinger($pl_id, $unntatt);}
	public function antall_forestillinger($pl_id, $unntatt=array()) {
		if(!isset($this->forestillinger))
			$this->_load_forestillinger($pl_id);
			
		if(!is_array($unntatt))
			$unntatt = array($unntatt);
		
		if(sizeof($unntatt)>0) {
			$antall_foretillinger = 0;
			foreach($this->forestillinger as $c_id => $rekkefolge) {
				if(in_array($c_id, $unntatt))
					continue;
				$antall_forestillinger++;
			}
			return $antall_forestillinger;
		}
		return sizeof($this->forestillinger);
	}
	
	private function _load_forestillinger($pl_id) {
		$this->forestillinger = array();
		$sql = new SQL( 'SELECT `c`.`c_id`, `b`.`order` FROM `smartukm_concert` as `c`, `smartukm_rel_b_c` as `b`
		                 WHERE `c`.`pl_id` = '.$pl_id.'
		                 AND `b`.`b_id` = '. $this->info['b_id'] .'
		                 AND `c`.`c_id` = `b`.`c_id`
		                 ORDER BY `c_start` ASC');
		$res = $sql->run();
		if(!$res)
			return;
		while($r = mysql_fetch_assoc($res))
			$this->forestillinger[$r['c_id']] = $r['order']+1;
	}
	
	public function ikke_videresendte_titler() {
		if(!isset($this->ikke_videresendte_titler))
			$this->_load_ikke_videresendte_titler();
		
		return $this->ikke_videresendte_titler;
		
	}
	private function _load_ikke_videresendte_titler() {
		$this->ikke_videresendte_titler = array();
		$sql = new SQL("SELECT `t_id` FROM `#form` WHERE `b_id` = '#bid'",
						array('form'=>$this->g('bt_form'), 'bid'=>$this->g('b_id')));

		$res = $sql->run();
		if($res&&mysql_num_rows($res)>0) {
			while($r = mysql_fetch_assoc($res)){
				$videresendt = new SQL("SELECT * FROM `smartukm_fylkestep`
										WHERE `b_id` = '#bid'
										AND `t_id` = '#tid'",
										array('bid'=>$this->g('b_id'), 'tid'=>$r['t_id']));
				$videresendt = $videresendt->run();
				if(mysql_num_rows($videresendt)!=0)
					continue;
				
				$this->ikke_videresendte_titler[] = new tittel($r['t_id'],$this->g('bt_form'));
			}
		}
	}

	////
	private function _load_titler($pl_id,$forwardToPLID=false,$uavhengig_av_monstring=false) {
		require_once('UKM/tittel.class.php');
			
		$this->titler = array();
		$place = new monstring($pl_id);
		$sql = new SQL("SELECT `t_id` FROM `#form` WHERE `b_id` = '#bid'",
						array('form'=>$this->g('bt_form'), 'bid'=>$this->g('b_id')));

		$res = $sql->run();
		if($res&&mysql_num_rows($res)>0) {
			while($r = mysql_fetch_assoc($res)){
				/// LUK UT TITLER HVIS FYLKESMØNSTRING
				if($place->g('type')=='fylke' && !$uavhengig_av_monstring) {
					$videresendt = new SQL("SELECT * FROM `smartukm_fylkestep`
											WHERE `b_id` = '#bid'
											AND `t_id` = '#tid'",
											array('bid'=>$this->g('b_id'), 'tid'=>$r['t_id']));
					$videresendt = $videresendt->run();
					if(mysql_num_rows($videresendt)==0)
						continue;
				}
				/// LUK UT TITLER HVIS LANDSMØNSTRING
				elseif($place->g('type')=='land' && !$uavhengig_av_monstring) {
					// !! !! !! OBS !! !! !! //
					// Er det her korrekt &aring; bruke forwardToPLID ?
					// Burde det ikke være $this->g('pl_id')?
					// 08.09.2012
					// 26.09.2012 Endret, tror logikken stemmer
					$videresendt = new SQL("SELECT * FROM `smartukm_fylkestep`
											WHERE `b_id` = '#bid'
											AND `t_id` = '#tid'
											AND `pl_id` = '#plid'",
											array('bid'=>$this->g('b_id'),
												  'tid'=>$r['t_id'],
												  /*'plid'=>$forwardToPLID));*/
												  'plid'=>$pl_id));
					$videresendt = $videresendt->run();
					if(mysql_num_rows($videresendt)==0) {
					// 20.01.2013 Lagt til sjekk nr 2 for at APIet skal håndtere gamle videresendinger
					$landstep = new SQL("SELECT * FROM `smartukm_landstep`
											WHERE `b_id` = '#bid'
											AND `t_id` = '#tid'",
											array('bid'=>$this->g('b_id'),
												  'tid'=>$r['t_id']));
	#				if($_SERVER['REMOTE_ADDR'] == '188.113.121.10')
	#					echo $landstep->debug();
					$landstep = $landstep->run();

						if(mysql_num_rows($landstep)==0) {
							continue;
						}
					}
						
				}

				$this->titler[] = new tittel($r['t_id'],$this->g('bt_form'));
			}
		}
	}
	
	public function titler($pl_id, $forwardToPLID=false, $uavhengig_av_monstring=false) {
		if(!isset($this->titler))
			$this->_load_titler($pl_id, $forwardToPLID, $uavhengig_av_monstring);
		return $this->titler;
	}
	
	public function varighet($pl_id, $forwardToPLID=false) {
		if(!isset($this->info['varighet']))
			$this->kalkuler_titler($pl_id, $forwardToPLID);
		return $this->g('varighet');
	}
	
	public function tid($pl_id, $forwardToPLID=false) {
		if(!isset($this->info['tid']))
			$this->kalkuler_titler($pl_id, $forwardToPLID);
		return $this->g('tid');
	}
	
	public function kalkuler_titler($pl_id, $forwardToPLID=false) {
		$titler = $this->titler($pl_id);
		$varighet = 0;
		foreach($titler as $tittel) {
			$varighet += (int) $tittel->g('varighet');
		}
		$this->info['varighet'] = $varighet;
		$this->info['tid'] = $this->_secondtominutes($varighet);
		$this->info['antall_titler'] = sizeof($titler);
		$this->info['antall_titler_lesbart'] = sizeof($titler)==1?'1 tittel':sizeof($titler).' titler';
	}
	
	private function _secondtominutes($sec) {
		$q = floor($sec / 60);
		$r = $sec % 60;
		
		if ($q == 0)
			return $r.' sek';
			
		if ($r == 0)
			return $q.' min';
		
		return $q.'m '.$r.'s';
	}
	
	public function editable() {
		if($this->g('b_status')==8)
			return true;
		return time() > (7*24*3600 + $this->g('b_subscr_time'));
	}
	
	
	public function warnings($pl_id) {
		if(!isset($this->info['warnings']))
			$this->_load_warnings($pl_id);
		return $this->g('warnings');
	}
	
	private function _load_warnings($pl_id){
		$warning = array();
		###
		$this->personer();
		if(sizeof($this->personer)==0)
			$warning[] = 'innslaget har ingen deltakere';
		###
		if(!in_array($this->g('bt_id'),array(4,5,8,9))){
			$this->kalkuler_titler($pl_id);
			if($this->g('antall_titler')==0)
				$warning[] = 'innslaget har ingen titler (og vil derfor ikke kunne settes opp i et program)';
			if($this->g('antall_titler')>3)
				$warning[] = 'innslaget har tenkt &aring; delta '.$this->g('antall_titler').' titler';

			if($this->g('bt_id')==1) {
				if($this->g('varighet')<30)
					$warning[] = 'innslaget har en total varighet p&aring; '.$this->g('tid').' (mindre enn 10 sekunder)';
				if($this->g('varighet')>600)
					$warning[] = 'innslaget har en total varighet p&aring; '.$this->g('tid').' (mindre enn 10 sekunder)';
			}
		}
		$k = $this->g('b_kategori');
		$t = $this->g('td_demand');
		if(empty($t) && (in_array($k, array('musikk','dans','teater')) || substr($k,0,5)=='annet'))
			$warning[] = 'innslaget har ingen tekniske behov';
		
		$place = new monstring($pl_id);
		$forestillinger = $place->forestillinger();

		$this->info['warnings'] = ucfirst(implode(', ', $warning));
	}
	////
	
	
	
	public function statistikk_oppdater() {
	
		$sqldel = new SQLdel('ukm_statistics',
							 array('season' => $this->get('b_season'),
							 	   'b_id' => $this->get('b_id')));
		$sqldel->run();
		
		$this->loadGEO();
		if($this->get('b_status')==8) {
			foreach ($this->personer() as $p) { // behandle hver person
				$person = new person($p["p_id"]);
				
				$time = $this->get('time_status_8');
				
				if (strlen($time) <= 1) {
					$time = $this->get('b_season')."-01-01T00:00:01Z";
				} else {
					$time = date("Y-m-d\TH:i:s\Z" , $this->get('time_status_8'));
				}
				
				$kommuneID = $this->get("kommuneID");
				$fylkeID = $this->get("fylkeID");
				
				// PRE 2011 does not contain kommune in database.
				// Fake by selecting first kommune of mønstring
				if(empty($kommuneID)) {
/*
					$kommuneID = $monstring->info['kommuner'][0]['id'];
					$fylkeID = $monstring->get('fylke_id');
*/
				}
				
				$stats_info = array(
					"b_id" => $this->get("b_id"), // innslag-id
					"p_id" => $person->get("p_id"), // person-id
					"k_id" => $kommuneID, // kommune-id
					"f_id" => $fylkeID, // fylke-id
					"bt_id" => $this->get("bt_id"), // innslagstype-id
					"subcat" => $this->get("b_kategori"), // underkategori
					"age" => $person->getAge() == '25+' ? 0 : $person->getAge(), // alder
					"sex" => $person->kjonn(), // kjonn
					"time" =>  $time, // tid ved registrering
					"fylke" => false, // dratt pa fylkesmonstring?
					"land" => false, // dratt pa festivalen?
					"season" => $this->get('b_season') // sesong
				);
				
				// faktisk lagre det 
				$qry = "SELECT * FROM `ukm_statistics`" .
						" WHERE `b_id` = '" . $stats_info["b_id"] . "'" .
						" AND `p_id` = '" . $stats_info["p_id"] . "'" .
						" AND `k_id` = '" . $stats_info["k_id"] . "'"  .
						" AND `season` = '" . $stats_info["season"] . "'";
				$sql = new SQL($qry);
				
				// Sjekke om ting skal settes inn eller oppdateres
				if (mysql_num_rows($sql->run()) > 0)
					$sql_ins = new SQLins('ukm_statistics', array(
						"b_id" => $stats_info["b_id"], // innslag-id
						"p_id" => $stats_info["p_id"], // person-id
						"k_id" => $stats_info["k_id"], // kommune-id
						"season" => $stats_info["season"], // kommune-id
					) );
				else 
					$sql_ins = new SQLins("ukm_statistics");
				
				// Legge til info i insert-sporringen
				foreach ($stats_info as $key => $value) {
					$sql_ins->add($key, $value);
				}
				$sql_ins->run();
			}
		}
	}
}
