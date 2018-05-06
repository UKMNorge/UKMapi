<?php
	
class forestilling_v2 extends forestilling {
	// Midlertidig hack i påvente av omskriving
	var $id = null;
	var $navn = null;
	var $monstring_id = null;
	var $monstring = null;
	var $start = null;
	var $start_datetime = null;
	var $synlig_i_rammeprogram = null;
	var $synlig_detaljprogram = null;
	var $synlig_oppmotetid = false;
	var $oppmote_for = null;
	var $oppmote_delay = null;
	var $type = null;
	var $type_post_id = null;
	
	var $collection_innslag = null;
	
	public function __construct($c_id,$tekniskprove=false) {
		if( is_array( $c_id ) ) {
			$c_id = $c_id['c_id'];
		}

		parent::__construct($c_id,$tekniskprove);
		$this->setId( $this->info['c_id'] );
		$this->setNavn( utf8_encode( $this->info['c_name'] ) );
		$this->setStart( $this->info['c_start'] );
		$this->setSted( utf8_encode( $this->info['c_place'] ) );
		$this->setMonstringId( $this->info['pl_id'] );
		$this->setSynligRammeprogram( 'true' == $this->info['c_visible_program'] );
		$this->setSynligDetaljprogram( 'true' == $this->info['c_visible_detail'] );
		$this->setSynligOppmotetid( 'true' == $this->info['c_visible_oppmote'] );
		$this->setOppmoteFor( $this->info['c_before'] );
		$this->setOppmoteDelay( $this->info['c_delay'] );
		$this->setType( $this->info['c_type'] );
		$this->setTypePostId( $this->info['c_type_post_id'] );
	}
	
	public function getType() {
		return $this->type;
	}
	public function setType( $type ) {
		$this->type = $type;
		return $this;
	}
	
	public function getTypePostId() {
		return $this->type_post_id;
	}
	public function setTypePostId( $post_id ) {
		$this->type_post_id = $post_id;
		return $this;
	}
	
	public function setContext( $context ) {
		$this->context = $context;
		return $this;
	}
	public function getContext() {
		return $this->context;
	}	
	
	public function getAll() {
		// TODO: FIX THIS
		return $this->innslag();
	}

	/**
	 * Hent innslag i denne forestillingen.
	 *
	 * @return innslag collection
	**/
	public function getInnslag() {
		if( null == $this->innslag ) {
			$this->innslag = new innslag_collection( $this->getContext() );
		}
		return $this->innslag;
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
	 * Sett navn på innslag
	 *
	 * @param string $navn
	 * @return $this
	**/
	public function setNavn( $navn ) {
		$this->navn = $navn;
		return $this;
	}
	/**
	 * Hent navn på innslag
	 *
	 * @return string $navn
	**/
	public function getNavn() {
		if( empty( $this->navn ) ) {
			return 'Forestilling uten navn';
		}
		return $this->navn;
	}
	
	/**
	 * Sett navn på sted for hendelsen
	 *
	 * @param string $sted
	 * @return $this
	**/
	public function setSted( $sted ) {
		$this->sted = $sted;
		return $this;
	}
	/**
	 * Hent navn på sted for hendelsen
	 *
	 * @return string $sted
	**/
	public function getSted() {
		return $this->sted;
	}
	
	/**
	 * Sett mønstringsid (PLID)
	 *
	 * @param string $type
	 * @return $this
	**/
	public function setMonstringId( $pl_id ) {
		$this->monstring_id = $pl_id;
		return $this;
	}
	/**
	 * Hent mønstringsid (PLID)
	 *
	 * @param string $type
	 * @return $this
	**/
	public function getMonstringId() {
		return $this->monstring_id;
	}
	/**
	 * Hent mønstring (objektet)
	 *
	 * @return monstring
	**/
	public function getMonstring() {
		if( null == $this->monstring ) {
			$this->monstring = new monstring_v2( $this->getMonstringId() );
		}
		return $this->monstring;
	}
	
	/**
	 * Hent direktelenke til hendelsen
	 *
	 * @return string url
	**/
	public function getLink() {
		return $this->getMonstring()->getLink()
				 .'program/?hendelse='
				 . $this->getId();
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
	 * Hent nummer i rekken
	 *
	 * @param object innslag
	**/
	public function getNummer( $searchfor ) {
		// TODO: BRUK FUNKSJON SOM RETURNERER INNSLAGSOBJEKTER, IKKE GAMMEL FUNKSJON
		foreach( $this->getAll() as $order => $innslag ) {
			if( $searchfor->getId() == $innslag['b_id'] ) {
				return $order+1;
			}
		}
		return false;
	}

	/**
	 * Hent start-justering for oppmøte-beregning
	 *
	 * @return int minutter før forestillingsstart
	**/
	public function getOppmoteFor() {
		return $this->oppmote_for;
	}
	/**
	 * Sett start-justering for oppmøte-beregning
	 *
	 * @param int minutter
	 * @return this
	**/
	public function setOppmoteFor( $minutter ) {
		$this->oppmote_for = $minutter;
		return $this;
	}
	
	/**
	 * Hent justering per innslag for oppmøte-beregning
	 *
	 * @param int minutter
	 * @return int sekunder delay per innslag
	**/
	public function setOppmoteDelay( $minutter ) {
		$this->oppmote_delay = $minutter;
		return $this;
	}
	/**
	 * Sett justering per innslag for oppmøte-beregning
	 *
	 * @return this
	**/
	public function getOppmoteDelay() {
		return $this->oppmote_delay;
	}
	
	/**
	 * Hent oppmøtetidspunkt for et gitt innslag
	 *
	 * @return DateTime oppmøtetidspunkt
	**/
	public function getOppmoteTid( $searchfor ) {
		$oppmote = clone $this->getStart();
		$oppmote->sub( DateInterval::createFromDateString( $this->getOppmoteFor() . " minutes") );
		$oppmote->add( DateInterval::createFromDateString( ($this->getOppmoteDelay() * ($this->getNummer( $searchfor )-1)) . " minutes") );
		return $oppmote;
	}
	
	/**
	 * Skal forestillingen vises i rammeprogrammet?
	 *
	 * @return bool
	**/
	public function erSynligRammeprogram() {
		return $this->synlig_i_rammeprogram;
	}
	
	/**
	 * Set om forestillingen skal vises i rammeprogrammet
	 *
	 * @param bool synlig
	 * @return $this
	**/
	public function setSynligRammeprogram( $synlig ) {
		$this->synlig_i_rammeprogram = $synlig;
		return $this;
	}

	
	/**
	 * Skal forestillingen vises i rammeprogrammet?
	 *
	 * @return bool
	**/
	public function erSynligOppmotetid() {
		return $this->synlig_oppmotetid;
	}
	
	/**
	 * Set om forestillingen skal vises i rammeprogrammet
	 *
	 * @param bool synlig
	 * @return $this
	**/
	public function setSynligOppmotetid( $synlig ) {
		$this->synlig_oppmotetid = $synlig;
		return $this;
	}

	/**
	 * Skal detaljene for forestillingen vises?
	 *
	 * @return bool
	**/
	public function harSynligDetaljprogram() {
		return $this->synlig_detaljprogram;
	}
	public function erSynligDetaljProgram() {
		return $this->harSynligDetaljprogram();
	}
	
	/**
	 * Sett om rekkefølgen skal være tilgjengelig
	 *
	 * @param bool synlig
	 * @return $this
	**/
	public function setSynligDetaljprogram( $synlig ) {
		$this->synlig_detaljprogram = $synlig;
		return $this;
	}
}

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
	
	public function reCount() {
		$this->_reCount('order');
		#$this->_reCount('techorder'); // VI BRUKER IKKE LENGRE TEKNISKE PRØVER
		// KAN FORSÅVIDT IKKE GJØRE DET PÅ DENNE MÅTEN,
		// DA TEKNISKE PRØVER MED REKKEFØLGE 0 PÅ ALLE HAR EGEN FUNKSJONALITET
	}
	
	private function _reCount($order) {
		#echo '<h1>Re-count order: '. $order .' '. $this->g('c_name') .' ('. $this->g('c_id') .')</h1>';
		$sql = new SQL("SELECT *
						FROM `smartukm_rel_b_c`
						WHERE `c_id` = '#c_id'
						ORDER BY `#order` ASC",
					array('c_id'=>$this->g('c_id'), 'order' => $order)
					);
		#echo '<strong>'. $sql->debug() .'</strong><br />';
		$res = $sql->run();
		
		$count = 0;
		while( $r = mysql_fetch_assoc( $res ) ) {
			$update = new SQLins('smartukm_rel_b_c', array('c_id' => $this->g('c_id'), 'bc_id' => $r['bc_id']));
			$update->add($order, $count);
			#echo $update->debug();#. '<br />';
			$update->run();
			$count++;
		}				

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
		if( $bandResult ) {
			while( $bandRow = mysql_fetch_assoc( $bandResult ) ) {
				$this->bandRows[] = $bandRow;
				$this->index[] = $bandRow['b_id'];
				if($kunstutstilling && $bandRow['bt_id']!=3)
					$kunstutstilling = false;
			}
			
			$this->info['antall_innslag'] = mysql_num_rows($bandResult);
		} else {
			$this->info['antall_innslag'] = 0;
		}
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
		return $this->info['total_varighet'];
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

class concert extends forestilling {}
?>
