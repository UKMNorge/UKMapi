<?php

namespace UKMNorge\Wordpress;
use \Flashbag;
use UKMlogger;

require_once('UKM/logger.class.php');
require_once('UKM/flashbag.class.php');
require_once('UKM/inc/twig-admin.inc.php');

/**
 * Modul for å bygge plugins over
 * 
 * Bygger på UKMRFIDwp::UKMModul som igjen bygger på UKMVideresending::UKMModul
 * Kun denne skal brukes fra nå
 */
abstract class Modul {
    public static $view_data = [];
    public static $ajax_response = [];

    public static $flashbag = null;
    
    // ABSTRACT METHODS AND VARIABLES
    public static $action = null;
    abstract static function hook();
    abstract static function meny();
    
    /**
     * Initier modulen
     * 
     * @param string __DIR__
     */
    public static function init( $plugin_path ) {
        static::$flashbag = new Flashbag( basename( $plugin_path ) );
        static::$view_data = [
            'season' => get_option('season')
        ];
        if( isset( $_GET['action'] ) ) {
            static::setAction( $_GET['action'] );
        }
        self::setPluginPath( $plugin_path );
    }
    
    /**
     * Get Flashbag 
     * Instance of UKMflashbag.class
     * 
     * @return UKMflash
     */
    public static function getFlash() {
        return static::$flashbag;
    }

    /**
     * Get Flashbag
     * ALIAS: static::getFlash()
     * 
     * @return UKMflash
     */
    public static function getFlashbag() {
        return static::getFlash();
    }

    /**
     * Get include path for plugin
     * 
     * @return string $path_to_plugin
     */
    public static function getPluginPath() {
        $child = get_called_class();
        return static::$path_plugin;
    }

    /**
     * Render admin-GUI
     */
    public static function renderAdmin() {
        try {

			// Håndter lagring før visning
			if( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_GET['save'] ) ) {
				static::setupLogger();
				static::save( $_GET['save'] );
			}
			
			
			## ACTION CONTROLLER
            static::require('controller/'. static::getAction() .'.controller.php');
            
            ## RENDER
            echo TWIG( strtolower(static::getAction()) .'.html.twig', static::getViewData() , static::getPath(), true);

            // Hvis modulen bruker TwigJS
            if( file_exists( static::getTwigJsPath() ) ) {
                require_once('UKM/inc/twig-js.inc.php');
                echo TWIGjs( static::getTwigJsPath() );
            }
        } catch( Exception $e  ) {
            // Attempt to die gracefully
            $exceptionFile = static::getTwigPath('exception') . static::getAction() .'.html.twig';
            if( file_exists( $exceptionFile ) ) {
                echo TWIG( 
                    'exception.html.twig',
                    [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode()
                    ],
                    static::getPath(),
                    true
                );
                return;
            }
            // Graceful not possible. Throw it!
            throw $e;
        }
        return;
    }

    /**
     * Sett plugin path for inkludering av filer fra riktig sted
     * Initieres fra static::init()
     */
    public static function setPluginPath( $dir ) {
        static::$path_plugin  = rtrim( $dir, '/') .'/';
    }

    public static function getPath() {
        return static::$path_plugin;
    }

    public static function getTwigPath() {
        return static::getPath() .'twig/';
    }

    public static function getTwigJsPath() {
        return static::getTwigPath() .'js/';
    }

    /**
     * Hent hvilken viewAction som er aktive
     *
     * @return string
    **/
    public static function getAction() {
        return static::$action;
    }
    
    /**
     * Set aktiv viewAction
     * 
     * @param string $action
     * @return void
     */
    public static function setAction( $action ) {
        static::$action = $action;
    }
        
    /**
     * Hent alle view-data
     *
     * @return array
    **/
    public static function getViewData() {
        static::$view_data['flashbag'] = static::getFlashbag();
        return static::$view_data;
    }

    /**
     * Legg til viewdata
     * 
     * Tar i mot array med flere keys (ett parameter)
     * eller key, value (to parameter)
     *
     * @param [string|array] key eller [key => val]
     * @param [null|array] data hvis oppgitt key som string
     * @return void
    **/
    public static function addViewData( $key_or_array, $data=null ) {
        if( is_array( $key_or_array ) ) {
            static::$view_data = array_merge( static::$view_data, $key_or_array );
        } else {
            static::$view_data[ $key_or_array ] = $data;
        }
    }
	
	/**
	 * Default ajax handler
	 *
	 * @return void
	 */
	public static function ajax() {
		if( is_array( $_POST ) ) {
			self::addResponseData('POST', $_POST );
		}
		
		try {
			static::setupLogger();

			$controller = basename( $_POST['controller'] );
			if( $controller == 'save' ) {
				$controller = 'save/'. basename( $_POST['save'] );
            }
            
            if( isset( $_POST['module'] ) ) {
                $controller = basename( $_POST['module'] ) .'/'. $controller;
            }

			self::require('ajax/'. $controller .'.ajax.php');
		} catch( Exception $e ) {
			self::addResponseData('success', false);
			self::addResponseData('message', $e->getMessage() );
			self::addResponseData('code', $e->getCode() );
		}
		
		$data = json_encode( self::getResponseData() );
		echo $data;
		die();
	}	

    /**
     * Hent alle ajax response-data
     *
     * @return array
    **/
    public static function getResponseData() {
        return static::$ajax_response;
    }

    /**
     * Legg til ajax respons-data
     * 
     * Tar i mot array med flere keys (ett parameter)
     * eller key, value (to parameter)
     *
     * @param [string|array] key eller [key => val]
     * @param [null|array] data hvis oppgitt key som string
     * @return void
    **/
    public static function addResponseData( $key_or_array, $data=null ) {
        if( is_array( $key_or_array ) ) {
            static::$ajax_response = array_merge( static::$ajax_response, $key_or_array );
        } else {
            static::$ajax_response[ $key_or_array ] = $data;
        }
    }

	/**
	 * Require a file from the plugin directory
	 *
	 * @param string $file_path_in_plugin_dir
	 * @return void
	 */
    public function require( $file ) {
        if( strpos( $file, 'UKM/' ) === 0 ) {
            require_once( $file );
        } else {
            require_once( static::getPluginPath() . $file );
        }
	}
	
	public static function setupLogger() {
		## SETUP LOGGER
		global $current_user;
		get_currentuserinfo();
		require_once('UKM/logger.class.php'); 
		UKMlogger::setID( 'wordpress', $current_user->ID, get_option('pl_id') );
	}
}