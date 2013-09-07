<?php

class forestilling {
	public $info;
	
	public function __construct($c_id,$tekniskprove=false) {
		$concertSql = new SQL( 'SELECT *
								FROM `smartukm_concert` WHERE `c_id` = ' . $c_id );
										  
		$this->info = $concertSql->run( 'array' );
		$this->orderby = $tekniskprove ? 'techorder':'order';
		$this->m = new monstring($this->info['pl_id']);
	}
	
	public function duplicate() {
		$new_c = new SQLins('smartukm_concert');
		
		foreach($this->info as $key => $val)
			if($key == 'c_name')
				$new_c->add($key, $val.' kopi');
			elseif($key !== 'c_id')
				$new_c->add($key, $val);

		$new_c_res = $new_c->run();
		$duplicate = new forestilling($new_c->insid());


		$innslag = $this->innslag();
		foreach($innslag as $b) {
			$duplicate->leggtil($b['b_id']);
		}
		return $duplicate;
	}
	
	public function slett(){
		if(empty($this->info['c_id']))
			return false;
		
		$this->fjern_alle_innslag(true);
		$qry = new SQLdel('smartukm_concert', array('c_id'=>$this->g('c_id')));
		$res = $qry->run();
		
		return $res==1;
	}
	
	public function fjern($b_id) {
		if(!is_numeric($this->info['c_id'])||!is_numeric($b_id)||$b_id==0||$this->info['c_id']==0)
			return false;
		$qry = new SQLdel('smartukm_rel_b_c', array('c_id'=>$this->g('c_id'),'b_id'=>$b_id));
		$res = $qry->run();
		return $res==1;
	}
	
	public function fjern_alle_innslag($override=false){
		if($this->antall_innslag()!=1 && !$override)
			return false;
	
		$qry = new SQLdel('smartukm_rel_b_c', array('c_id'=>$this->g('c_id')));
		$res = $qry->run();
		return true;
	}
	
	public function ny_teknisk_rekkefolge($commaseparated_ids) {
		return $this->ny_rekkefolge($commaseparated_ids,false,true);
	}
	
	public function helt_ny_rekkefolge($rekkefolge_csv){
		// Funksjonen gjøres så komplisert for å berge en eventuell teknisk prøve
		if($this->g('c_id')==0)
			return false;
			
		if(empty($rekkefolge_csv))
			return false;

		$rekkefolge = explode(',', $rekkefolge_csv);
		
		// Legg til evt nye innslag
		foreach($rekkefolge as $b_id) {
			if(!$this->er_med($b_id))
				$this->leggtil($b_id);
		}

		// Sett rekkefølge i minus så vi kan gjenkjenne etter oppdatering
		$qry = new SQLins('smartukm_rel_b_c', array('c_id'=>$this->g('c_id')));
		$qry->add('order',-1);
		$qry->run();
		
		// Oppdater rekkefølgen for alle som skal være med
		$this->ny_rekkefolge($rekkefolge_csv);
		
		// Slett alle med rekkefølge -1 (dvs ikke fått ny plass, og skal fjernes)
		$qry = new SQLdel('smartukm_rel_b_c', array('c_id'=>$this->g('c_id'), 'order'=>-1));
		$qry->run();
		
		$this->_nullstill();
		return $this->antall_innslag();
	}
	
	private function _nullstill() {
		unset($this->info['antall_innslag']);
		unset($this->bandRows);
	}
	
	public function ny_rekkefolge($commaseparated_ids,$addIfNew=false,$tekniskprove=false) {
		$order = explode(',',$commaseparated_ids);
		$totRes = true;
		for($i=0; $i<sizeof($order);$i++) {
			$qry = new SQLins('smartukm_rel_b_c',array('c_id'=>$this->g('c_id'),
													   'b_id'=>$order[$i]));
			$qry->add(($tekniskprove?'techorder':'order'), $i+1);
				
			$res = $qry->run();
			#echo '<br />' . $qry->debug() . ' = ' . var_export($res,true);
			if($addIfNew && $res == 0) {
				$check = new SQL("SELECT `b_id` FROM `smartukm_rel_b_c`
								WHERE `c_id` = '#cid'
								AND `b_id` = '#bid'",
								array('cid'=>$this->g('c_id'),
									  'bid'=>$order[$i]));
				$check = $check->run();
				if(mysql_num_rows($check)==0){
					$ins = new SQLins('smartukm_rel_b_c');
					$ins->add('c_id', $this->g('c_id'));
					$ins->add('b_id', $order[$i]);
					$ins->add('techorder',time());
					$res = $ins->run();
					if($totRes&&!$res)
						$totRes = false;
				}
			}
			if($totRes&&$res==-1)
				$totRes = false;
		}
		return $totRes;
	}
	
	public function update($field, $post_key=false) {
		if(!$post_key)
			$post_key = $field;
		if($_POST[$post_key] == $_POST['log_current_value_'.$post_key])
			return true;

		$qry = new SQLins('smartukm_concert', array('c_id'=>$this->info['c_id']));
		$qry->add($field, $_POST[$post_key]);
#		echo $qry->debug();
		$qry->run();

		
		UKMlog('smartukm_concert',$field,$post_key,$this->info['c_id']);
	}
	
	
	/**
	 * concert function.
	 * 
	 * Henter ut informasjon om en spesifikk forestilling
	 *
	 * @access public 
	 * @param int $c_id
	 * @return array $ret
	 */
	public function concert( $c_id )
	{
		/* Henter ut en konsert */

		$ret['c_name'] = utf8_encode($this->info['c_name']);
		$ret['c_place'] = utf8_encode($this->info['c_place']);
		
		
		return $ret;
	}
	
	public function synlig($type='ramme') {
		if($type=='ramme')
			return $this->g('c_visible_program') == 'true';
		return $this->g('c_visible_detail') == 'true';
		
	}
	
	public function er_utstilling() {
		if(!isset($this->info['kunstutstilling']))
			$this->concertBands();
			
		return $this->info['kunstutstilling'];
	}
	
	public function leggtil($b_id) {
		$this->addBand($b_id);
	}
	public function addBand($b_id) {
		$lastorder = new SQL("SELECT `order`
							  FROM `smartukm_rel_b_c`
							  WHERE `c_id` = '#cid'
							  ORDER BY `order` DESC
							  LIMIT 1",
							  array('cid' => $this->info['c_id']));
		$lastorder = $lastorder->run('field','order');
		$order = (int)$lastorder+1;
		
		$qry = new SQLins('smartukm_rel_b_c');
		$qry->add('b_id', $b_id);
		$qry->add('c_id', $this->info['c_id']);
		$qry->add('order', $order);
		$qry->run();		
	}
	
	public function antall_innslag() {
		if(!isset($this->info['antall_innslag']))
			$this->concertBands();
		
		return $this->info['antall_innslag'];
	}
	
/*
	private function _load_antall_innslag() {
		
		$bandSql = new SQL( 'SELECT `b_id`, `order` 
							 FROM `smartukm_rel_b_c` 
							 WHERE `c_id` = ' . $this->info['c_id'] . '
							 ORDER BY `'.$this->orderby.'` ASC');
		$bandResult = $bandSql->run();
		$this->info['antall_innslag'] = mysql_num_rows($bandResult);
	}
*/
	
	public function er_med($b_id) {
		if(!isset($this->bandRows))
			$this->concertBands();
		// Forestillingen har ingen innslag
		if(!is_array($this->index))
			return false;
		// Sjekk om innslaget er med eller ikke
		return in_array($b_id, $this->index);
	}
	
	/**
	 * concertBands function.
	 * 
	 * Henter ut ID til alle band som tilhører en forestilling
	 *
	 * @access public
	 * @param int $c_id
	 * @return arraya $bandRows
	 */
	public function innslag() {
		return $this->concertBands();
	}
	public function concertBands(){
		if(isset($this->bandRows))
			return $this->bandRows;
		
		$bandSql = new SQL( 'SELECT `b`.`b_id`,`b`.`bt_id`, `rel`.`order` 
							 FROM `smartukm_rel_b_c` AS `rel`
							 JOIN `smartukm_band` AS `b` ON (`b`.`b_id` = `rel`.`b_id`) 
							 WHERE `c_id` = ' . $this->info['c_id'] . '
							 AND `b`.`b_status` = \'8\''						##### LAGT TIL 03.01.2013
							.'ORDER BY `'.$this->orderby.'` ASC');
		$bandResult = $bandSql->run();
		$bandRows = array();
		
		$kunstutstilling = true;
		if( $bandResult )
		while( $bandRow = mysql_fetch_assoc( $bandResult ) ) {
			$this->bandRows[] = $bandRow;
			$this->index[] = $bandRow['b_id'];
			if($kunstutstilling && $bandRow['bt_id']!=3)
				$kunstutstilling = false;
		}
		
		$this->info['antall_innslag'] = mysql_num_rows($bandResult);
		if($this->info['antall_innslag']==0)
			$kunstutstilling=false;
		$this->info['kunstutstilling'] = $kunstutstilling;
		
		if(!isset($this->bandRows))
			$this->bandRows = array();
		return $this->bandRows;
	}
	
	public function varighet(){
		if(isset($this->info['total_varighet']))
			return $this->info['total_varighet'];

		if(!isset($this->bandRows))
			$this->concertBands();
		
		foreach($this->bandRows as $i) {
			$inn = new innslag($i['b_id']);
			if($this->m->g('type')!='kommune')
				$inn->videresendte(get_option('pl_id'));
			$this->info['total_varighet'] += (int)$inn->varighet($this->g('pl_id'));
		}
	}
	
	public function tid(){
		if(!isset($this->info['total_varighet']))
			$this->varighet();
		return $this->_secondtominutes($this->info['total_varighet']);
	}
	private function _secondtominutes($sec,$long=false) {
		$hours = floor($sec / 3600);
		$minutes = floor(($sec / 60) % 60);
		$seconds = $sec % 60;
	
		$h = $long ? ' time'.($h==1?'':'r') : 't';
		$m = $long || ($hours==0 && $seconds==0) ? ' min' : 'm';
		$s = $long || ($hours==0 && $minutes==0) ? ' sek' : 's';	
		
		$str = '';
	
		if($hours > 0)
			$str .= $hours.$h.' ';
	
		if( !empty($str) || $minutes > 0)
			$str .= $minutes.$m.' ';
		
		if( empty($str) || $seconds > 0)
			$str .= $seconds.$s;	
	
		return $str;
	}

	## Returnerer verdien til attributten (key)
	public function g($key) {	return $this->get($key);	}
	public function get($key) {
		if(is_array($this->info[$key]))
			return $this->info[$key];
			
		return utf8_encode($this->info[$key]);	
	}
		
	public function starter() {
		return ucfirst(__(date( 'l', $this->info['c_start']))) .' '. date('d.m', $this->info['c_start']) . ' kl. ' . date('H:i', $this->info['c_start']);
	}
	
	// Henter program
	public function getForestillingData( $c_id=false ) {
		if(!$c_id)
			$c_id = $this->info['c_id'];
		global $post;
		// Get all "innslag"
		$forestilling = $this->concertBands($c_id);
		
		$innslag = array();
		
		// Loop results and add bandname and genere
		$program = array();
		for ($i = 0; $i < sizeof($forestilling); $i++) {
			$innslag = new innslag($forestilling[$i]['b_id']);
			$nr = ($i+1);
			if ($nr < 10)
				$nr = "0".$nr;
			$program[] = $nr.'. '.$innslag->get('b_name').' ('.trim($innslag->get('b_sjanger')).')<br>';
			
			// Get related items
			$arrInnslag[] = $innslag->related_items();
		}
		
		// Get the "summary-news" for this concert
		$news_id = get_post_meta($post->ID, 'UKMviseng_forestilling_news_id', true);
		$news = get_post($news_id);
		$news_image = get_the_post_thumbnail( $news_id );
		return array('news'=>$news, 'news_image'=>$news_image, 'program'=>$program, 'innslag'=>$arrInnslag);
	}
}

class concert extends forestilling {
	
}

?>