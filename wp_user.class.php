<?php
class wp_UKM_user {
	
	private $load_strategy = null;
	private $load_id = null;
	
	public $id = null;
	public $navn = null;
	public $epost = null;
	public $kommune_id = null;
	public $kommune = null;
	public $fylke_id = null;
	public $passord = null;
	public $wp_id = null;
	public $lock = false;
	


	/**
	 * 
	 * STATIC FUNCTIONS
	 *
	**/
	
	/**
	 * Hent en WP-brukers ID
	 *
	 * @param string $load_strategy
	 * @param (string|int) $load_id
	 * @return (int|bool:false) $user_id
	**/
	public static function getWPUserId( $load_id, $load_strategy ) {
		$user = wp_UKM_user::getWPUser( $load_id, $load_strategy );
		if( is_object( $user ) && isset( $user->ID ) ) {
			return $user->ID;
		}
		return false;
	}
	
	/**
	 * Hent en WP-bruker (WP_User)
	 *
	 * @param string $load_strategy
	 * @param (string|int) $load_id
	 *
	 * @return WP_User
	**/
	public static function getWPUser( $load_id, $load_strategy ) {
		switch( $load_strategy ) {
			case 'WPID':
				$user = get_user_by( 'ID', $load_id );
				break;
			case 'UKMID':
				if( $this->getNavn() == null ) {
					throw new Exception('Kan ikke finne WP-bruker etter UKM-ID når bruker ikke eksisterer i UKM-tabellen');
				}
				$user = get_user_by( 'login', $this->getNavn() );
				break;
			case 'email':
				$user = get_user_by( 'email', $load_id );
				break;
			case 'username':
				$user = get_user_by( 'login', $load_id );
				break;
			default:
				throw new Exception('Ukjent load strategy: '. $load_strategy );
		}
		return $user;
	}

	/**
	 * 
	 * INSTANCE FUNCTIONS
	 *
	**/

	/**
	 * __construct
	 *
	 * @param $id_row_or_email
	 * IF: array: load by row
	 * IF: numeric, specify type (WP|UKM)
	 * IF: string: specify type (email|username)
	 *
	**/
	public function __construct( $id_row_or_email, $type='WP' ) {
		// ARRAY
		if( is_array( $id_row_or_email ) ) {
			$data = $id_row_or_email;

			if( isset( $data['b_name'] ) ) {
				$this->load_strategy = 'username';
				$this->load_id = $data['b_name'];
			} else {
				throw new Exception('WP_user(array) mangler minst ett felt');
			}
		}
		// NUMERIC
		elseif( is_numeric( $id_row_or_email ) ) {
			if( $type == 'WP' ) {
				$data = $this->_loadByWPID( $id_row_or_email );
			} elseif( $type == 'UKM' ) {
				$data = $this->_loadByUKMID( $id_row_or_email );
			} else {
				throw new Exception('WP_user(numeric) krever type-parameter (WP|UKM)');
			}
		}
		// STRING
		elseif( is_string( $id_row_or_email ) ) {
			if( $type == 'email' ) {
				$data = $this->_loadByEmail( $id_row_or_email );
			} elseif( $type == 'username' ) {
				$data = $this->_loadByUsername( $id_row_or_email );
			} else {
				throw new Exception('WP_user(string) krever type-parameter (email|username)');
			}
		}
		// ERROR
		else {
			throw new Exception('WP_user krever id, rad eller e-postadresse for å opprette objekt');
		}
		
		$this->_loadByRow( $data );
	}
	
	/**
	 * Eksisterer brukeren i klartekst-tabellen?
	 *
	 * @return bool $exists
	**/
	public function exists() {
		return is_numeric( $this->getId() );
	}
	
	/**
	 * Eksisterer brukeren i WP-tabellen med brukere
	 *
	 * @return bool
	**/
	public function existsWP() {
		if( !$this->exists() ) {
			$user = wp_UKM_user::getWPUser( $this->load_id, $this->load_strategy );
		} else {
			$user = wp_UKM_user::getWPUser( $this->getNavn(), 'username' );
		}
		if( $user == null || $user == false ) {
			return false;
		}
		return true;
	}

	/**
	 * Get ID
	 *
	 * @return int ID
	**/
	public function getId(){
		return $this->id;
	}
	
	/**
	 * getNavn
	 * 
	 * @return string username
	**/
	public function getNavn(){
		return $this->navn;
	}

	/**
	 * setNavn
	 *
	 * @param string $navn
	 * @return $this
	**/
	public function setNavn( $navn ){
		$this->navn = $navn;
		return $this;
	}
	
	/**
	 * getPassord
	 *
	 * @return string password
	**/
	public function getPassord(){
		return $this->passord;
	}
	
	/**
	 * setPassord
	 *
	 * @param string $passord
	 * @return $this
	**/
	public function setPassord( $passord ){
		$this->passord = $passord;
		return $this;
	}
	
	/**
	 * getEpost
	 *
	 * @return string e-mail
	**/
	public function getEpost(){
		return $this->epost;
	}

	/**
	 * setEpost
	 *
	 * @param string $epost
	 * @return $this
	**/
	public function setEpost( $epost ){
		if( $this->getLock() ) {
			throw new Exception('Kan ikke endre e-postadresse på system-låste brukere');
		}
		$this->epost = $epost;
		return $this;
	}	

	/**
	 * setId
	 *
	 * @param int id
	 * @return $this
	**/
	public function setId( $id ){
		$this->id = $id;
		return $this;
	}
	
	/**
	 * getKommune
	 * 
	 * @return kommune $kommune
	**/
	public function getKommune(){
		if( $this->kommune == null ) {
			$this->kommune = new kommune( $this->kommune_id );
		}
		return $this->kommune;
	}
	
	/**
	 * setKommune
	 *
	 * @param kommune $kommune
	 * @return $this
	**/
	public function setKommune( $kommune ){
		$this->kommune = $kommune;
		$this->kommune_id = $kommune->getId();
		return $this;
	}
	
	/**
	 * getFylke
	 *
	 * @return fylke $fylke
	**/
	public function getFylke(){
		if( $this->fylke == null ) {
			$this->fylke = new fylke( $this->fylke_id );
		}
		return $this->fylke;
	}
	
	/**
	 * setFylke
	 *
	 * @param fylke $fylke
	 * @return $this
	**/
	public function setFylke( $fylke ){
		$this->fylke = $fylke;
		$this->fylke_id = $fylke->getId();
		return $this;
	}
	
	/**
	 * getWPID
	 *
	 * @return int WP_user->ID
	**/
	public function getWPID(){
		return $this->wp_id;
	}
	
	/**
	 * setWPID
	 *
	 * @param int $wp_id
	 * @return $this
	**/
	public function setWPID( $wp_id ){
		$this->wp_id = $wp_id;
		return $this;
	}
	
	/**
	 * getLock
	 * Hvorvidt brukeren selv får lov til å endre e-postadresse
	 *
	 * @return bool $locked
	**/
	public function getLock(){
		return $this->lock;
	}
	
	/**
	 * setLock
	 * 
	 * @param bool $lock
	 * @return bool $lock
	**/
	public function setLock( $lock ){
		$this->lock = $lock;
		return $this;
	}


	/**
	 * get Load Strategy
	 *
	 * @return string $load_strategy
	**/
	protected function _getLoadStrategy() {
		return $this->load_strategy;
	}

	/**
	 * get Load ID
	 *
	 * @return (string|int) $load_id
	**/
	protected function _getLoadId() {
		return $this->load_id;
	}	
	private function _loadByRow( $data ) {
		if( is_array( $data ) ) {
			$this->id 			= $data['b_id'];
			$this->navn 		= $data['b_name'];
			$this->passord 		= $data['b_password'];
			$this->epost 		= $data['b_email'];
			$this->kommune_id	= (int) $data['b_kommune'];
			$this->fylke_id		= (int) $data['b_fylke'];
			$this->wp_id		= $data['wp_bid'];
			$this->lock			= $data['lock_email'] == 'true';
		} elseif( is_object( $data ) ) {
			$this->id 			= $data->b_id;
			$this->navn 		= $data->b_name;
			$this->passord 		= $data->b_password;
			$this->epost 		= $data->b_email;
			$this->kommune_id	= (int) $data->b_kommune;
			$this->fylke_id		= (int) $data->b_fylke;
			$this->wp_id		= $data->wp_bid;
			$this->lock			= $data->lock_email == 'true';
		}
	}
	
	/**
	 * Load by Wordpress user Id
	**/
	private function _loadByWPID( $id ) {
		$this->load_strategy = 'WPID';
		$this->load_id = $id;
		global $wpdb;
		return $wpdb->get_row("SELECT * FROM `ukm_brukere` WHERE `wp_id` = '". $id ."'");
	}
	/**
	 * Load by UKM id
	**/
	private function _loadByUKMID( $id ) {
		$this->load_strategy = 'UKMID';
		$this->load_id = $id;
		global $wpdb;
		return $wpdb->get_row("SELECT * FROM `ukm_brukere` WHERE `b_id` = '". $id ."'");
	}
	/**
	 * Load by email
	**/
	private function _loadByEmail( $email ) {
		$this->load_strategy = 'email';
		$this->load_id = $email;
		global $wpdb;
		return $wpdb->get_row("SELECT * FROM `ukm_brukere` WHERE `b_email` = '". $email ."'");
	}
	/**
	 * Load by username
	**/
	private function _loadByUsername( $username ) {
		$this->load_strategy = 'username';
		$this->load_id = $username;
		global $wpdb;
		return $wpdb->get_row("SELECT * FROM `ukm_brukere` WHERE `b_name` = '". $username ."'");
	}
}