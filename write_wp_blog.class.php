<?php

require_once('UKM/fylker.class.php');

	
class write_wp_blog {
	
	/**
	 * Hent alle fylkesbrukere (inkl URG-brukerne)
	 * 
	 * @return array $fylkesbrukere array['fylkeID'] = [brukernavn => epost, urg-brukernavn => epost]
	**/
	public static function getFylkesbrukere() {
		$brukere = [];
		foreach( fylker::getAll() as $fylke ) {
			$brukere[ $fylke->getURLsafe() ][ $fylke->getURLsafe() ] = $fylke->getURLsafe() .'@ukm.no';
			$brukere[ $fylke->getURLsafe() ][ 'urg-'.$fylke->getURLsafe() ] = $fylke->getURLsafe() .'@urg.ukm.no';
		}
		return $brukere;
	}
	
	/**
	 * Fjern gitt bruker fra gitt blogg
	 *
	 * @param int $user_id
	 * @param int $blog_id
	**/
	public static function fjernBrukerFraBlogg( $user_id, $blog_id ) {
		return remove_user_from_blog($user_id, $blog_id);
	}
	
	/**
	 * Legg til gitt bruker fra gitt blogg
	 * 
	 * @param int $user_id
	 * @param int $blog_id
	 * @param string $role
	**/
	public static function leggTilBrukerTilBlogg( $user_id, $blog_id, $role ) {
		return add_user_to_blog( $blog_id, $user_id, $role );
	}
	
	/**
	 * Legg til mange brukere til gitt blogg med gitt rolle
	 *
	 * @param array $users [username => email]
	 * @param int $blog_id
	 * @param string $role
	 *
	 * @return array $reports
	**/
	public static function addUsersToBlog( $users, $blog_id, $role) {
		$rapporter = [];
		foreach( $users as $username => $email ) {
			$rapport_bruker = array(
								'success' 		=> true,
								'brukernavn' 	=> $username,
								'rolle'			=> $role
							);
			$user = wp_UKM_user::getWPUser( $username, 'username' );
			// Fant bruker
			if( is_object( $user ) && isset( $user->ID ) ) {
				try {
					write_wp_blog::leggTilBrukerTilBlogg( $user->ID, $blog_id, $rapport_bruker['rolle'] );
				} catch( Exception $e ) {
					$rapport_bruker['success']	= false;
					$rapport_bruker['error']	= 'FEIL: '. $e->getMessage();
				}
			}
			// Fant ikke fylkesbruker (what, wait, ..but how?)
			else {
				$rapport_bruker['success']		= false;
				$rapport_bruker['error']		= 'Fant ikke brukeren. Opprett først.';
			}
			$rapporter[] = $rapport_bruker;
		}
		return $rapporter;
	}
	
	/**
	 * Eksisterer gitt path som blogg?
	 *
	 * @param string $path (/$path/)
	 * @return bool eksisterer
	**/
	public static function getIdByPath( $path ) {
		return domain_exists( UKM_HOSTNAME, $path );
	}

	/**
	 * Eksisterer gitt path som blogg?
	 *
	 * @param string $path (/$path/)
	 * @return bool eksisterer
	**/
	public static function eksisterer( $path ) {
		return is_numeric( write_wp_blog::getIdByPath( $path ) );
	}
	
	/**
	 * Oppretter en ny blogg tilknyttet en mønstring
	 *
	 * @param monstring_v2 $monstring
	**/
	public static function create( $monstring ) {
		$path = write_wp_blog::getPathFromMonstring( $monstring );
		
		if( write_wp_blog::eksisterer( $path ) ) {
			throw new Exception('Write_WP_Blog: kan ikke opprette blog '. $path .' da URL\'en allerede er i bruk');
		}
		
		## OPPRETT BLOGG
		$blog_id = create_empty_blog( UKM_HOSTNAME, $path, $monstring->getNavn() );

		write_wp_blog::setDefaultSettings( $blog_id );
		write_wp_blog::setMonstringData( $blog_id, $monstring );
		write_wp_blog::setDefaultContent( $blog_id, $monstring->getType() );
		
		return $blog_id;
	}
	
	/**
	 * Oppdater bloggen til å stemme med mønstringen
	 *
	 * @param monstring_v2 $monstring
	**/
	public static function updateBlogFromMonstring( $monstring ) {
		$path = write_wp_blog::getPathFromMonstring( $monstring );
		$blog_id = write_wp_blog::getIdByPath( $path );
		
		write_wp_blog::setDefaultSettings( $blog_id );
		write_wp_blog::setMonstringData( $blog_id, $monstring );
		write_wp_blog::setDefaultContent( $blog_id, $monstring->getType() );
	}
	
	/**
	 * Hent path fra gitt mønstring
	 * 
	 * Burde sjekke for slash, og ikke bare legge til rått
	 * 
	 * @param monstring_v2 $monstring
	 * @return string $path
	**/
	public static function getPathFromMonstring( $monstring ) {
		return '/'. $monstring->getPath() .'/';
	}
	
	/**
	 * Sett default settings
	 * Setter alle mønstrings-uavhengige settings på bloggen
	 *
	 * @param int $blog_id
	**/
	public static function setDefaultSettings( $blog_id ) {
		# ADDS META OPTIONS TO NEW SITE
		$meta = array(
					  'show_on_front'=>'page',
					  'page_on_front'=>'2',
					  'template'=>'UKMresponsive',
					  'stylesheet'=>'UKMresponsive',
					  'current_theme'=>'UKM Responsive'
					 );
		write_wp_blog::applyMeta( $blog_id, $meta );
	}
	
	/**
	 * Sett mønstringsdata
	 *
	 * @param int $blog_id
	 * @param monstring_v2 $monstring
	**/
	public static function setMonstringData( $blog_id, $monstring ) {
		$meta = array(
					'blogname'			=> $monstring->getNavn(),
					'blogdescription'	=> 'UKM i ' . $monstring->getNavn(),
					'fylke'				=> $monstring->getFylke()->getId(),
					'site_type'			=> $monstring->getType(),
					'pl_id'				=> $monstring->getId(),
					'ukm_pl_id' 		=> $monstring->getId(),
					'season'			=> $monstring->getSesong(),
				);
		if( $monstring->getType() == 'kommune' ) {
			$meta['kommuner'] = implode(',', $monstring->getKommuner()->getIdArray() );
		}
		write_wp_blog::applyMeta( $blog_id, $meta );
	}
	
	/**
	 * Sett standardinnhold (sider og kategorier)
	 *
	 * @param int $blog_id
	 * @param string $type (kommune|fylke|land)
	**/
	public static function setDefaultContent( $blog_id, $type='kommune' ) {
		switch_to_blog( $blog_id );
		
		// Kategorier
		$cat_defaults = array(
							'cat_name' => 'Nyheter',
							'category_description' => 'nyheter' ,
							'category_nicename' => 'Nyheter',
							'category_parent' => 0,
							'taxonomy' => 'category'
						);
		wp_insert_category($cat_defaults);
		
		// Sider
		$sider = array(
					['id' => 'bilder', 'name' => 'Bilder', 'viseng' => 'bilder' ],
					['id' => 'pameldte', 'name' => 'Påmeldte', 'viseng' => 'pameldte'],
					['id' => 'program', 'name' => 'Program', 'viseng' => 'program'],
					['id' => 'kontaktpersoner', 'name' => 'Kontaktpersoner', 'viseng' => 'kontaktpersoner'],
				);
		if( $type !== 'kommune' ) {
			$sider[] = ['id' => 'lokalmonstringer', 'name' => 'Lokalmønstringer', 'viseng' => 'lokalmonstringer'];
		}
		
		foreach( $sider as $side ){
			$page = array(
						'post_type' => 'page',
						'post_title' => $side['name'],
						'post_status' => 'publish',
						'post_author' => 1,
						'post_slug' => $side['id'],
					);
			// Finnes siden fra før?
			$eksisterer = get_page_by_path( 'bah-'.$side['id'] );
			if( $eksisterer == null ) {
				$page_id = wp_insert_post($page);
			} else {
				$page_id = $eksisterer->ID;
			}
			if( isset( $side['viseng'] ) && !empty( $side['viseng'] ) ) {
				add_post_meta($page_id, 'UKMviseng', $side['viseng']);
			}
		}
		
		// Slett "hei verden"
		$hello_world = get_page_by_path('hei-verden', OBJECT, 'post');
		if( is_object( $hello_world ) ) {
			wp_delete_post( $hello_world->ID );
		}
		
		restore_current_blog();
	}
	
	/**
	 * Sett et array med metadata på bloggen
	**/
	public static function applyMeta( $blog_id, $meta ) {
		foreach($meta as $key => $value) {
			add_blog_option($blog_id, $key, $value);
			update_blog_option($blog_id, $key, $value, true);
		}
	}
}