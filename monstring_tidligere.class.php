<?php
require_once('UKM/monstring.class.php');
	class tidligere_monstring {
		public function __construct($pl_id, $season){
			$now = new monstring( $pl_id );
			$type = $now->get('type');
			switch( $type ) {
				case 'land':
					$monstring = new landsmonstring( $season );
					$this->monstring = $monstring->monstring_get();
					break;
				case 'fylke':
					$fylke = $now->get('fylke_id');
					$monstring = new fylke_monstring( $fylke, $season );
					$this->monstring = $monstring->monstring_get();
					break;
				default:
					/* GAMMEL SØKEALGORITME */
					/* Looper pl_pl-tabellen for historikk knyttet til mønstringen */
					$found = false;
					$search = true;
					$search_pl_id = $pl_id;
					while( $search ) {
						$qry = new SQL("SELECT *
										FROM `smartukm_rel_pl_pl`
										WHERE `pl_new` = '#new'",
										array('new'=>$search_pl_id)
									   );
						$res = $qry->run();
						if(SQL::numRows($res)==0)
							$search = false;
						while($r = SQL::fetch($res)){
							if( $season == $r['season'] ) {
								$this->monstring = new monstring( $r['pl_new'] );
								$search = false;
								$found = true;
							} else {
								$search_pl_id = $r['pl_old'];
							}
						}
					}
					
					/* NY ALTERNATIV SØKEALGORITME */
					/* Bruker kun den første kommunen, noe som er trøblete ved splittede fellesmønstringer! */
					if( !$found ) {
						$kommuner = $now->get('kommuner');
						$kommune = $kommuner[0]['id'];
						$monstring = new kommune_monstring( $kommune, $season );
						$this->monstring = $monstring->monstring_get();
					}
			}
		}
		public function monstring_get() {
			return $this->monstring;
		}
	}
	
	class postnummer_monstring {
		public function __construct( $postnummer, $season ) {
			$kommune_id = new SQL("SELECT `k_id`
								   FROM `smartukm_postalplace`
								   WHERE `postalcode` = '#postnummer'",
								   array('postnummer'=>$postnummer));
			$kommune_id = $kommune_id->run('field', 'k_id');
			if( is_numeric($kommune_id) && $kommune_id > 0 ) {
				$monstring = new kommune_monstring( $kommune_id, $season );
				$this->monstring = $monstring->monstring_get();
				if( $this->monstring->get('pl_id') == false ) {
					$this->monstring = false;
				} else {
					$this->monstring = $this->monstring;
				}
			} else {
				$this->monstring = false;
			}
		}
		
		public function monstring_get() {
			return $this->monstring;
		}
	}

	/**
	 * OBS OBS OBS OBS OBS OBS OBS OBS
	 *
	 * BRUK HELLER monstringer_v2::kommune( $kommune, $season )
	 *
	**/
	class kommune_monstring_v2 extends kommune_monstring {
		public function monstring_get() {
			return new monstring_v2( $this->pl_id );
		}
	}

	class kommune_monstring{
		public function __construct($kommune,$season) {
			$qry = new SQL("SELECT `pl_id`
							FROM `smartukm_rel_pl_k`
							WHERE `k_id` = '#kommune'
							AND `season` = '#season'",
						array('kommune'=>$kommune,'season'=>$season));
			$this->pl_id = $qry->run('field','pl_id');
		}
		
		public function monstring_get() {
			return new monstring($this->pl_id);
		}
	}

	class fylke_monstring_v2 extends fylke_monstring {
		public function monstring_get() {
			return new monstring_v2( $this->pl_id );
		}
	}
	class fylke_monstring{
		public function __construct($fylke,$season) {
			$qry = new SQL("SELECT `pl_id`
							FROM `smartukm_place`
							WHERE `pl_fylke` = '#fylke'
							AND `season` = '#season'",
						array('fylke'=>$fylke,'season'=>$season));
			$this->pl_id = $qry->run('field','pl_id');
		}
		
		public function monstring_get() {
			return new monstring($this->pl_id);
		}
	}
	class landsmonstring{
		public function __construct($season) {
			$qry = new SQL("SELECT `pl_id`
							FROM `smartukm_place`
							WHERE `pl_fylke` = '123456789'
							AND `pl_kommune` = '123456789'
							AND `season` = '#season'",
						array('season'=>$season));
			$this->pl_id = $qry->run('field','pl_id');
		}
		
		public function monstring_get() {
			return new monstring($this->pl_id);
		}
	}

