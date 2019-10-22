<?php
require_once('UKM/sql.class.php');
require_once('UKM/bilde_storrelse.class.php');

class bilde {
	var $id = null;
	var $rel_id = null;
	var $blog_id = null;
	var $blog_url = null;
	var $album_id = null;
	var $album = null;
	var $kommune_id = null;
	var $kommune = null;
	var $season = null;
	var $pl_id = null;
	var $monstring = null;
	var $pl_type = null;
	var $innslag_id = null;
	var $innslag = null;
	
	var $author_id = null;
	var $author = null;
	
	var $sizes = null;
	
	private $post_meta = null;	# PostMeta skal ikke aksesseres eksternt, men pakkes med getters and setters
	
	static $table = 'ukmno_wp_related';

	/**
	 * Hent et innslagsbilde
	 * 
	 * @param $bilde as integer (ukm_bilder::id) or associative database row joined from ukm_bilder and wp_related)
	 *
	**/
	public function __construct( $bilde ) {
		if( is_int( $bilde ) ) {
			$this->_loadByID( $bilde );
		} elseif( is_array( $bilde ) ) {
			$this->_loadByArray( $bilde );	
		} else {
			throw new Exception( 'BILDE: Could not recognize image ID parameter. '
								.'Expects integer or database row. '
								.'Given '. (is_array($bilde) ? 'Array' : get_class( $bilde )) 
								);
		}
	}
	
	/**
	 * Sett bilde-ID
	 *
	 * @param integer $id 
	 *
	 * @return $this
	**/
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}
	/**
	 * Hent bilde-ID
	 *
	 * @return integer $id
	**/
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Sett relasjonsID (fra ukmno_wp_related)
	 *
	 * Tabellen joines alltid inn, både fra getBilder() og getBildeById
	 * og både ukmno_wp_related.rel_id og ukm_bilder.id vil alltid være tilgjengelig.
	 * Begge disse unike nøklene representerer kun ett bilde,
	 * og vi har derfor to måter å finne samme bilde på.
	 *
	 * Videresendingsssystemet (og getValgtBilde()) bruker rel_id,
	 * og kunne like gjerne brukt id. Men, ettersom de er det samme, bruker
	 * vi rel_id, som det opprinnelig var kodet.
	 *
	 * @param integer $rel_id
	 * @return $this
	**/ 
	public function setRelId( $rel_id ) {
		$this->rel_id = $rel_id;
		return $this;
	}
	/**
	 * Hent relasjonsID (fra ukmno_wp_related)
	 *
	 * @return int $rel_id
	**/
	public function getRelId() {
		return $this->rel_id;
	}
	
	/**
	 * Sett Blogg-id (wordpress)
	 *
	 * @param integer $blog_id
	 *
	 * @return $this
	**/
	public function setBlogId( $blog_id ) {
		$this->blog_id = $blog_id;
		return $this;
	}
	/**
	 * Hent Blogg-id (wordpress)
	 *
	 * @return integer $blog_id
	**/
	public function getBlogId() {
		return $this->blog_id;
	}
	
	/**
	 * Sett Blogg-url (wordpress)
	 *
	 * @param string $blog_url
	 *
	 * @return $this
	**/
	public function setBlogUrl( $blog_url ) {
		$this->blog_url = $blog_url;
		return $this;
	}
	/**
	 * Hent Blogg-url (wordpress)
	 *
	 * @return string $blog_url
	**/
	public function getBlogUrl() {
		return $this->blog_url;
	}	
    
    /**
     * Sett Post-ID (wordpress)
     *
     * @param Integer $post_id
     * @return $this
     */
    public function setPostId( $post_id ) {
        $this->post_id = $post_id;
        return $this;
    }

    /**
     * Hent Post-ID (wordpress)
     *
     * @return Integer $post_id
     */
    public function getPostId() {
        return $this->post_id;
    }

	/**
	 * Sett album-id
	 * Hvis bildet er lastet opp som en del av en forestilling, hent forestilling-ID
	 *
	 * @param integer album_id
	 *
	 * @return $this;
	**/
	public function setAlbumId( $album_id ) {
		$this->album_id = $album_id;
		return $this;
	}
	
	/**
	 * Hent album-id
	 *
	 * @return integer album_id
	**/
	public function getAlbumId() {
		return $this->album_id;
	}
	
	/**
	 * Sett kommune
	 *
	 * @param integer kommune_id
	 *
	 * @return $this
	**/
	public function setKommuneId( $kommune_id ) {
		$this->kommune_id = $kommune_id;
		return $this;
	}
	
	/**
	 * Hent kommuneID
	 *
	 * @return integer kommune_id
	**/
	public function getKommuneId() {
		return $this->kommune_id;
	}
	
	/**
	 * Hent kommune-objektet
	 *
	 * @return object kommune
	**/
	public function getKommune() {
		// Hvis kommune-objektet allerede er lastet inn
		if( null !== $this->kommune ) {
			return $this->kommune;
		}
		
		// Kommune er ikke satt
		if( null == $this->getKommuneId() ) {
			return false;
		}
		
		require_once('UKM/kommune.class.php');
		$this->kommune = new kommune( $this->getKommuneId() );
		
		return $this->kommune;
	}
	
	/**
	 * Set sesong
	 *
	 * @param integer sesong
	 *
	 * @return this
	**/
	public function setSesong( $sesong ) {
		$this->sesong = $sesong;
		return $this;
	}
	
	/**
	 * Hent sesong
	 *
	 * @return integer sesong
	**/
	public function getSesong() {
		return $this->sesong;
	}
	
	/**
	 * Sett MønstringsID (PlId)
	 *
	 * @param integer pl_id
	 *
	 * @return $this
	**/
	public function setPlId( $pl_id ) {
		$this->pl_id = $pl_id;
		return $this;
	}
	
	/**
	 * Hent MønstringsID (PlId)
	 *
	 * @return integer pl_id
	**/
	public function getPlId() {
		return $this->pl_id;
	}
	
	/**
	 * Hent Mønstring
	 *
	 * @return monstring
	**/
	public function getMonstring() {
		// Mønstring er allerede lastet inn
		if( null !== $this->monstring ) {
			return $this->monstring;
		}
		
		// Mønstring er ikke satt
		if( null == $this->getPlId() ) {
			return false;
		}
		
		require_once('UKM/monstring.class.php');
		$this->monstring = new monstring( $this->getPlId() );
		if( false == $this->monstring->getId() ) {
			return false;
		}
		
		return $this->monstring;
	}
	
	/**
	 * Sett mønstringstype (pl_type)
	 * Brukes for å gruppere bilder, og det er unødvendig tungt å hente mønstringsobjektet for det
	 *
	 * @param string pl_type
	 * 
	 * @return $this
	**/
	public function setMonstringType( $pl_type ) {
		$this->pl_type = $pl_type;
		return $this;
	}
	
	/**
	 * Hent mønstringstype (pl_type)
	 * Brukes for å gruppere bilder, og det er unødvendig tungt å hente mønstringsobjektet for det
	 *
	 * @return string pl_type (kommune,fylke,land)
	**/
	public function getMonstringType() {
		return $this->pl_type;
	}
	
	/**
	 * Sett fotograf (WP Author)
	 *
	 * @param integer author_id
	 *
	 * @return $this;
	**/
	public function setAuthorId( $author_id ) {
		$this->author_id = $author_id;
		return $this;
	}
	
	/**
	 * Hent fotograf-ID
	 *
	 * @return integer author_id
	**/
	public function getAuthorId() {
		return $this->author_id;
	}
	
	/**
	 * Hent fotograf
	 *
	 * @return wp_author
	**/
	public function getAuthor() {
		// Author-objektet er allerede lastet inn
		if( null !== $this->author ) {
			return $this->author;
		}
		
		// Det er ikke satt author-id
		if( null == $this->getAuthorId() ) {
			return false;
		}
		
		// Hent inn author
		require_once('UKM/wp_author.class.php');
		$this->author = new wp_author( $this->getAuthorId() );
		
		// Author finnes ikke lengre
		if( null == $this->author->getId() ) {
			return false;
		}
		
		return $this->author;
	}
	
	/** 
	 * Sett InnslagId
	 *
	 * @param int innslag_id
	 *
	 * @return $this;
	**/
	public function setInnslagId( $innslag_id ) {
		$this->innslag_id = $innslag_id;
		return $this;
	}
	
	/**
	 * Hent InnslagId
	 *
	 * @return int $innslag_id
	**/
	public function getInnslagId() {
		return $this->innslag_id;
	}
	
	/**
	 * Hent Innslag
	 *
	 * @return innslag
	**/
	public function getInnslag() {
		// Innslaget er allerede lastet
		if( null !== $this->innslag ) {
			return $this->innslag;
		}
		
		// Innslag er ikke spesifisert (burde ikke gå an)
		if( null == $this->getInnslagId() ) {
			return false;
		}
		
		require_once('UKM/innslag.class.php');
		$innslag = new innslag_v2( $this->getInnslagId() );
		
		// Innslaget finnes ikke
		if( null == $innslag->getId() ) {
			return false;
		}
		
		return $innslag;
	}
	
	/**
	 * Hent gitt bilde fra ukm_bilder::id
	 *
	 * @param $bilde integer ukm_bilder::id
	 * @return void
	 *
	 */
	private function _loadByID( $id ) {
		$SQL = new SQL("SELECT * 
						FROM `ukm_bilder`
						JOIN `#table` ON (`#table`.`post_id` = `ukm_bilder`.`wp_post` AND `#table`.`b_id` = `ukm_bilder`.`b_id`)
						WHERE `ukm_bilder`.`id` = '#id'
						",
						array('table'=>self::$table,
							  'id'=>$id)
						);
		$res = $SQL->run();
		$row = SQL::fetch( $res );
		$this->_loadByArray( $row );
	}
	
	/**
	 * Hent gitt bilde fra databaserad (joinet ukmno_wp_related+ukm_bilder)
	 *
	 * @param associative row gitt bilde
	 * @return void
	 *
	**/
	private function _loadByArray( $bilde ) {
		$this->setId( $bilde['id'] );
		$this->setRelId( $bilde['rel_id'] );
		$this->setBlogId( $bilde['blog_id'] );
		$this->setBlogUrl( $bilde['blog_url'] );
        $this->setPostId( $bilde['post_id'] );

		$this->setAlbumId( $bilde['c_id'] );
		$this->setKommuneId( $bilde['b_kommune'] );		
		$this->setSesong( $bilde['b_season'] );
		$this->setPlId( $bilde['pl_id'] );
		$this->setMonstringType( $bilde['pl_type'] );
        $this->setInnslagId( $bilde['b_id'] );
        

		$this->post_meta	= unserialize( $bilde['post_meta'] );
		
		if( isset( $this->post_meta['author'] ) ) {
			$this->setAuthorId( $this->post_meta['author'] );
		}
		
		foreach( array('thumbnail','medium','large','lite') as $size ) {
			if( isset( $this->post_meta['sizes'][ $size ] ) ) {
				$this->addSize( $size, $this->post_meta['sizes'][$size] );
			}
		}
		$this->addSize( 'original', $this->post_meta['file'] );
	}
	/**
	 * Legg til bildestørrelse (URL til bildet i forskjellige størrelser)
	 *
	 * @param string $id (brukt for å hente ut bildet)
	 * @param array $data (bildedata: filnavn, bredde, høyde, mime-type)
	 *
	 * @return $this
	**/
	public function addSize( $id, $data ) {
		// Originalstørrelsen inneholder ikke størrelsesdata
		if( 'original' == $id ) {
			$file = $data;
			$data = array();
			$data['file'] = $file;
			$data['width'] = 0;
			$data['height'] = 0;
			$data['mime-type'] = false;
		}

		// Beregn paths	
		if( UKM_HOSTNAME == 'ukm.no'  ) {
			$basefolder = 'wp-content/blogs.dir/'. $this->getBlogId() .'/files/';
		} else {
			$basefolder = 'wp-content/uploads/sites/'. $this->getBlogId() .'/';
		}
		$data['path_int'] = $basefolder;
		$data['path_ext'] = 'http://'. UKM_HOSTNAME .'/'. $basefolder;

		// Opprett bilde
		$this->sizes[ $id ] = new bilde_storrelse( $data );		

		return $this;
	}
	
	/**
	 * Hent en størrelse (eller original hvis størrelsen ikke finnes
	 *
	 * @param string $id størrelse
	 *
	 * @return bilde_storrelse
	 *
	**/
	public function getSize( $id, $id2=false ) {
		if( isset( $this->sizes[ $id ] ) ) {
			return $this->sizes[ $id ];
		}
		
		if( $id2 != false && isset( $this->sizes[ $id2 ] ) ) {
			return $this->sizes[ $id2 ];
		}
		
		if( isset( $this->sizes['original'] ) ) {
			return $this->sizes[ 'original' ];
		}
		
		return false;
	}
	
	/**
	 * Kalkuler data fra metadata-array
	 *
	 * @return void
	 *
	**/
	private function _calc_data( $bilde ) {
		$this->post_meta = unserialize($bilde['post_meta']);		
		$bilde['post_meta'] = $this->loadAuthor($bilde['post_meta']);
		$bilde['post_meta'] = $this->missingLarge($bilde['post_meta']);
	}
}
?>