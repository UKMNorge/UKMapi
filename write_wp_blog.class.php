<?php

require_once('UKM/Autoloader.php');
require_once('UKM/write_wp_user.class.php');
require_once('UKM/write_monstring.class.php');

	
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
	 * Finn alle fylkesbrukere og legg de til gitt blogg
	 * 
	 * @param monstring_v2 $monstring
	 * @param int $blog_id
	 * @return array $rapport
	**/
	public static function leggTilFylkesbrukereTilBlogg( $monstring, $blog_id ) {
		$fylkesbrukere = write_wp_blog::getFylkesbrukere();
		// Noen fylker skal vi ikke jobbe så mye med
		// (jukse-fylker brukt av systemet)
		try {
			// kaster exception hvis fylket "ikke finnes" / ikke finnes
			$fylke_urlname = $monstring->getFylke()->getURLsafe();
			// Sjekker om fylket har fylkesbrukere
			if( !isset( $fylkesbrukere[ $fylke_urlname ] ) ) {
				throw new Exception('WRITE_WP_BLOG: Har ingen fylkesbrukere!');
			}
		} catch( Exception $e ) {
			return array(
				array(
					'success' 		=> false,
					'brukernavn' 	=> $monstring->getNavn(),
					'error'			=> 'Fylket har ingen fylkesbrukere ('. $e->getMessage().')'
				)
			);
		}
		
		/**
		 * Legg til fylkesbrukere til bloggen
		**/
		return write_wp_blog::addUsersToBlog( 
			$fylkesbrukere[ $fylke_urlname ],
			$blog_id,
			'editor'
		);
	}

	/**
	 * 
	 * Opprett kommunebrukere hvis de ikke eksisterer
	 * Lag en liste over brukere
	 *
	**/
	public static function leggTilKommunebrukerTilBlogg( $kommune, $blog_id, $return='addUsersToBlog' ) {
		try {
			$user = write_wp_UKM_user::finnEllerOpprettKommuneBruker( $kommune );
		} catch( Exception $e ) {
			return array(
				'success'	=> false,
				'error'		=> 'Kunne ikke opprette bruker ('. $e->getMessage() .')',
				'brukernavn'=> $kommune->getURLsafe(),
			);
		}
		

		$addUserToBlog = write_wp_blog::addUserToBlog(
			$user->getNavn(),
			$blog_id,
			'editor'
		);
		
		$addUserToBlog['object'] = $user;
		
		return $addUserToBlog;
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
			$rapporter[] = write_wp_blog::addUserToBlog( $username, $blog_id, $role );
		}
		return $rapporter;
	}

	/**
	 * Legg til en bruker til gitt blogg med gitt rolle
	 *
	 * @param string $username
	 * @param int $blog_id
	 * @param string $role
	 *
	 * @return array $report
	**/
	public static function addUserToBlog( $username, $blog_id, $role ) {
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
		
		return $rapport_bruker;
	}
	
	public static function fjernKommuneBrukerFraBlogg( $kommune, $blog_id ) {
		if( get_class( $kommune ) != 'kommune' ) {
			throw new Exception('WRITE_WP_BLOG: Kan ikke fjerne kommunebruker når kommune (param 1) ikke er kommune-objekt');
		}
		write_wp_blog::controlBlogId( $blog_id, 'Kan ikke fjerne kommunebruker');
		
		try {
			// Prøv å hente bruker
			$user_id = write_wp_UKM_user::getWPUserId( $kommune->getURLsafe(), 'username' );
			// Hvis en merkelig feil har oppstått
			if( !is_numeric( $user_id ) ) {
				throw new Exception('Fant ikke kommune-brukeren ('. $kommune->getURLsafe() .')');
			}
			// Faktisk fjern brukeren
			write_wp_blog::fjernBrukerFraBlogg( $user_id, $blog_id );
			// Rapport til twig
			$rapport = array(
				'name'		=> $kommune->getURLsafe(),
				'success' 	=> true,
				'message'	=> 'Kommunebruker fjernet fra bloggen',
			);
		}
		// Kunne ikke fjerne kommune-brukeren fra bloggen
		catch( Exception $e ) {
			$rapport = array(
				'name'		=> $kommune->getURLsafe(),
				'success'	=> false,
				'error'		=> 'Fjernet ikke kommunebruker ('. $kommune->getNavn() .': '. $e->getMessage() .')',
			);
		}
		return $rapport;
	}
	
	public static function fjernFylkesBrukereFraBlogg( $kommune, $blog_id ) {
		if( get_class( $kommune ) != 'kommune' ) {
			throw new Exception('WRITE_WP_BLOG: Kan ikke fjerne fylkesbrukere når kommune (param 1) ikke er kommune-objekt');
		}
		write_wp_blog::controlBlogId( $blog_id, 'Kan ikke fjerne fylkesbrukere');

		$rapporter = [];
		try {
			$alle_fylkesbrukere = write_wp_blog::getFylkesbrukere();
			$fylkesbrukere = $alle_fylkesbrukere[ $kommune->getFylke()->getURLsafe() ];

			foreach( $fylkesbrukere as $username => $email ) {
				// Prøv å hente bruker
				$user_id = write_wp_UKM_user::getWPUserId( $username, 'username' );
				// Hvis en merkelig feil har oppstått
				if( !is_numeric( $user_id ) ) {
					throw new Exception('Fant ikke fylkesbrukeren ('. $username .')');
				}
				// Faktisk fjern brukeren
				write_wp_blog::fjernBrukerFraBlogg( $user_id, $blog_id );
				// Rapport til twig
				$rapport = array(
					'name'		=> $kommune->getURLsafe(),
					'success' 	=> true,
					'message'	=> 'Kommunebruker fjernet fra bloggen',
				);
				$rapporter[] = $rapport;
			}
		}
		// Kunne ikke fjerne kommune-brukeren fra bloggen
		catch( Exception $e ) {
			$rapport = array(
				'name'		=> $kommune->getURLsafe(),
				'success'	=> false,
				'error'		=> 'Fjernet ikke kommunebruker ('. $kommune->getNavn() .': '. $e->getMessage() .')',
			);
		}
		return $rapporter;
	}
	
	
	public static function avlys( $monstring ) {
		$kommune = $monstring->getKommune();
		$rapport = new stdClass();
		$rapport->success = false;

		// Finn bloggen
		try {
			$rapport->path = write_wp_blog::getPathFromMonstring( $monstring );
			$rapport->blog_id = write_wp_blog::getIdByPath( $rapport->path );
			write_wp_blog::controlPath( $rapport->path, 'Kunne ikke avlyse mønstring');
			write_wp_blog::controlBlogId( $rapport->blog_id, 'Kunne ikke avlyse mønstring');
		} catch( Exception $e ) {
			throw new Exception( 'WRITE_WP_BLOG: Avbrøt avlysning før noen database-endringer ble gjort! ('. $e->getMessage() .')');
		}
		
		// Avlys mønstringen (og trekk ut kommune)
		try {
			write_monstring::avlys( $monstring );
		} catch( Exception $e ) {
			throw new Exception('WRITE_WP_BLOG: Kunne ikke avlyse mønstringen! ('. $e->getMessage() .')' );
		}
		
		// Fjern alle brukere fra bloggen
		$rapport_brukere = write_wp_blog::fjernBrukereFraBlogg( $rapport->blog_id );

		// Lagre data for identifisering av hva som har vært her
		update_blog_option( $rapport->blog_id, 'kommuner', $kommune->getId() );
		update_blog_option( $rapport->blog_id, 'status_monstring', 'avlyst' );

		$rapport->success = true;

		return $rapport;
	}
	
	public static function splitt( $blog_id, $monstring ) {
		write_wp_blog::controlBlogId( $blog_id, 'BlogId from');

		$rapport = new stdClass();
		// Lagre kommuneID'er	
		update_blog_option( $blog_id, 'kommuner', implode(',', $monstring->getKommuner()->getIdArray() ) );
		// Sett status splittet
		update_blog_option( $blog_id, 'status_monstring', 'splittet' );

		// Fjern alle brukere
		$rapport->brukere = write_wp_blog::fjernBrukereFraBlogg( $blog_id );
		
		return $rapport;
	}	
	
	public static function flytt( $blog_id, $monstring ) {
		write_wp_blog::controlBlogId( $blog_id, 'BlogId from');

		$rapport = new stdClass();
		// Lagre kommuneID'er	
		update_blog_option( $blog_id, 'kommuner', implode(',', $monstring->getKommuner()->getIdArray() ) );
		// Sett status splittet
		update_blog_option( $blog_id, 'status_monstring', 'flyttet' );

		// Fjern alle brukere (denne skal jo ikke vedlikeholdes av noen andre enn systemet)
		$rapport->brukere = write_wp_blog::fjernBrukereFraBlogg( $blog_id );

		return $rapport;
	}
	
	public static function flippBlogger( $path_from, $path_to, $path_temp ) {
		/**
		 * KONTROLLER INPUT-DATA
		**/
		if( !write_wp_blog::eksisterer( $path_from ) ) {
			throw new Exception('WRITE_WP_BLOG Kan ikke flippe blogg da $path_from ('. $path_from.') ikke eksisterer');
		}
		if( !write_wp_blog::eksisterer( $path_temp ) ) {
			throw new Exception('WRITE_WP_BLOG Kan ikke flippe blogg da $path_temp ('. $path_temp.') ikke eksisterer');
		}
		if( write_wp_blog::eksisterer( $path_to ) ) {
			throw new Exception('WRITE_WP_BLOG Kan ikke flippe blogg da $path_to ('. $path_to.')  allerede eksisterer');
		}
		
		$blog_id_from = write_wp_blog::getIdByPath( $path_from );
		$blog_id_temp = write_wp_blog::getIdByPath( $path_temp );

		write_wp_blog::controlBlogId( $blog_id_from, 'BlogId from');
		write_wp_blog::controlBlogId( $blog_id_from, 'BlogId temp');


		$rapport = new stdClass();

		/**
		 * FLYTT BLOGGENE
		**/
		// FLYTT DAGENS BLOGG TIL NY PATH
		try {
			$rapport->move_from = write_wp_blog::moveBlog( $path_from, $path_to );
		} catch( Exception $e ) {
			$rapport->move_from = new stdClass();
			$rapport->move_from->success = false;
			$rapport->move_from->error = $e->getMessage();
		}
		
		// HVIS DAGENS BLOGG BLE FLYTTET
		if( $rapport->move_from->success ) {
			// FLYTT TEMP-BLOGG TIL DAGENS PATH
			try {
				$rapport->move_temp = write_wp_blog::moveBlog( $path_temp, $path_from );
			} catch( Exception $e ) {
				$rapport->move_temp = new stdClass();
				$rapport->move_temp->success = false;
				$rapport->move_temp->error = $e->getMessage();
			}
		}
		// HVIS DAGENS BLOGG IKKE BLE FLYTTET, IKKE PRØV Å FLYTTE TEMP-BLOGGEN
		else {
			$rapport->move_temp = new stdClass();
			$rapport->move_temp->success = false;
			$rapport->move_temp->error = 
				'Prøvde ikke å flytte temp-blogg da flytting av nåværende blogg '. 
				'('. $path_from .') ikke ble flyttet'
			;
		}
		return $rapport;
	}
	
	public static function fjernBrukereFraBlogg( $blog_id ) {
		if( $blog_id == 1 ) {
			throw new Exception(
				'WRITE_WP_CLASS: Stopper fjernBrukereFraBlogg da den prøver å jobbe '.
				'mot hovedsiden (UKM for ungdom)'
			);
		}
		if( !is_numeric( $blog_id ) ) {
			throw new Exception(
				'WRITE_WP_CLASS: Kan ikke fjerne brukere fra blogg når blog_id ikke er numerisk verdi'
			);
		}

		$rapporter = [];
		$users = get_users( array('blog_id' => $blog_id ) );

		foreach( $users as $user ) {
			if( $user->ID == 1 ) {
				continue;
			}

			$rapport_bruker = new stdClass();
			$rapport_bruker->id = $user->ID;
			$rapport_bruker->name = $user->get('user_login');
			$res = write_wp_blog::fjernBrukerFraBlogg( $user->ID, $blog_id );
			if( $res == true ) {
				$rapport_bruker->success	= true;
				$rapport_bruker->message	= 'Brukeren ble fjernet fra bloggen';
			} else {
				$rapport_bruker->success	= false;
				$rapport_bruker->message	= implode(',', $res->get_error_messages());
			}
			$rapporter[] = $rapport_bruker;
		}
		return $rapporter;
	}
	
	
	public static function moveBlog( $from, $to ) {
		/**
		 * Kontroller at gitte paths er gyldige
		**/
		if( !write_wp_blog::eksisterer( $from ) ) {
			throw new Exception('WRITE_WP_BLOG::moveBlog() fant ikke fra-blogg på '. $from );
		}
		if( write_wp_blog::eksisterer( $to ) ) {
			throw new Exception('WRITE_WP_BLOG::moveBlog() fant allerede en blogg på '. $to );
		}
		
		$blog_id_fra = write_wp_blog::getIdByPath( $from );
		write_wp_blog::controlBlogId( $blog_id_fra, 'Flytt blogg');
		write_wp_blog::controlPath( $from, 'Flytt blogg');
		write_wp_blog::controlPath( $to, 'Flytt blogg');
		
		$domain = 'http' . (UKM_HOSTNAME == 'ukm.no' ? 's':'') .'://'. get_blog_details( $blog_id_fra )->domain;

		$response = new stdClass();
		$response->success	= false;
		$response->blog_id	= $blog_id_fra;
		$response->from		= $from;
		$response->to		= $to;		
		$response->domain	= $domain;
		
		$res1 = update_blog_details( $blog_id_fra, array('path' => $to) );
		$res2 = update_blog_option( $blog_id_fra, 'siteurl', $domain.$to );
		$res3 = update_blog_option( $blog_id_fra, 'home', $domain.$to );
		
		if( $res1 && $res2 && $res3 ) {
			$response->success = true;
		} else {
			$response->success = false;
			$response->error = 'Følgende feil skjedde når blogg '. $blog_id_fra .' skulle oppdateres:';
			if( !$res1 ) {
				$response->error .= 'Blog path ble ikke oppdatert (update_blog_details(array(path=>'.$to.')))';
			}
			if( !$res2 ) {
				$response->error .= 'Blog siteurl ble ikke oppdatert (update_blog_option('. $domain.$to .'))';
			}
			if( !$res3 ) {
				$response->error .= 'Blog home ble ikke oppdatert. (update_blog_option('. $domain.$to .'))';
			}
		}
		return $response;
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
	public static function create( $monstring, $customPath=false ) {
		if( $customPath != false ) {
			$path = $customPath;
		} else {
			$path = write_wp_blog::getPathFromMonstring( $monstring );
		}
		write_wp_blog::controlPath( $path, 'Kunne ikke opprette blogg');
		
		if( write_wp_blog::eksisterer( $path ) ) {
			throw new Exception('WRITE_WP_BLOG: kan ikke opprette blog '. $path .' da URL\'en allerede er i bruk');
		}
		
		## OPPRETT BLOGG
		$blog_id = create_empty_blog( UKM_HOSTNAME, $path, $monstring->getNavn() );
		write_wp_blog::controlBlogId( $blog_id, 'Kunne ikke oppdatere ny blogg');

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
	public static function updateBlogFromMonstring( $monstring, $customPath=false ) {
		if( $customPath != false ) {
			$path = $customPath;
		} else {
			$path = write_wp_blog::getPathFromMonstring( $monstring );
		}
		$blog_id = write_wp_blog::getIdByPath( $path );
		
		write_wp_blog::controlBlogId( $blog_id, 'updateBlogFromMonstring');
		write_wp_blog::controlPath( $path, 'updateBlogFromMonstring');

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
		write_wp_blog::controlBlogId( $blog_id, 'Kunne ikke sette default settings');

		# ADDS META OPTIONS TO NEW SITE
		$meta = array(
					  'show_on_front'		=>'posts',
					  'page_on_front'		=>'2',
					  'template'			=>'UKMresponsive',
					  'stylesheet'			=>'UKMresponsive',
					  'current_theme'		=>'UKM Responsive',
					  'status_monstring'	=> false
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
		write_wp_blog::controlBlogId( $blog_id, 'Kunne ikke sette mønstringsdata');

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
		write_wp_blog::controlBlogId( $blog_id, 'Kunne ikke opprette default-content');
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
					['id' => 'forside', 'name' => 'Forside', 'viseng' => null],
					['id' => 'nyheter', 'name' => 'Nyheter', 'viseng' => null],
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
			$eksisterer = get_page_by_path( $side['id'] );
			if( $eksisterer == null ) {
				$page_id = wp_insert_post($page);
			} else {
				$page_id = $eksisterer->ID;
			}
			if( isset( $side['viseng'] ) && !empty( $side['viseng'] ) ) {
				// Først delete, så add, fordi update_post_meta ikke gjør nok
				// (hvis current_value er et array, vil update_post_meta 
				// ikke gjøre noe/oppdatere alle verdiene (uvisst)
				//
				// VISENG håndterer arrays i visningen, men det er likevel greit å 
				// ha riktig data.
				delete_post_meta($page_id, 'UKMviseng');
				add_post_meta($page_id, 'UKMviseng', $side['viseng']);
			}
		}
		
		// Slett "hei verden"
		$hello_world = get_page_by_path('hei-verden', OBJECT, 'post');
		if( is_object( $hello_world ) ) {
			wp_delete_post( $hello_world->ID );
		}

		$page_on_front = get_page_by_path('forside');
		$page_for_posts = get_page_by_path('nyheter');
		
		// Sett standard visningssider
		update_blog_option( $blog_id, 'show_on_front', 'posts'); // 2019-02-07: endret til posts for at paginering skal funke
		update_blog_option( $blog_id, 'page_on_front', $page_on_front->ID);
		update_blog_option( $blog_id, 'page_for_posts', $page_for_posts->ID);

		restore_current_blog();
	}
	
	public static function controlPath( $path, $feedbackId ) {
		if( !is_string( $path ) ) {
			throw new Exception($feedbackId .': Gitt path er ikke en string');
		}
		if( empty( $path ) ) {
			throw new Exception($feedbackId .': Gitt path er tom');
		}
	}
	
	public static function controlBlogId( $blog_id, $feedbackId ) {
		if( empty( $blog_id ) ) {
			throw new Exception($feedbackId .': Gitt BlogId er tom');
		}
		if( !is_numeric( $blog_id ) ) {
			throw new Exception($feedbackId .': Gitt BlogId er ikke numerisk');
		}
		if( $blog_id == 1 ) {
			throw new Exception($feedbackId .': Gitt BlogId er 1 (hovedbloggen!)');
		}
	}
	
	/**
	 * Sett et array med metadata på bloggen
	**/
	public static function applyMeta( $blog_id, $meta ) {
		write_wp_blog::controlBlogId( $blog_id, 'applyMeta');

		foreach($meta as $key => $value) {
			add_blog_option($blog_id, $key, $value);
			update_blog_option($blog_id, $key, $value, true);
		}
	}
}
