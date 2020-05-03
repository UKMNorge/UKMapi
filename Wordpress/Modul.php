<?php

namespace UKMNorge\Wordpress;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Flashbag;
use UKMNorge\Log\Logger;

require_once('UKM/inc/twig-admin.inc.php');

/**
 * Modul for å bygge plugins over
 * 
 * Bygger på UKMRFIDwp::UKMModul som igjen bygger på UKMVideresending::UKMModul
 * Kun denne skal brukes fra nå
 */
abstract class Modul {
    public static $arrangement;

    public static $view_data = [];
    public static $ajax_response = [];

    public static $flashbag = null;
    
    // ABSTRACT METHODS AND VARIABLES
    static $action = null;
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
        if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
            static::$view_data['POST'] = $_POST;
        }
        if( isset( $_GET['action'] ) ) {
            static::setAction( $_GET['action'] );
        }
        static::setPluginPath( $plugin_path );
    }

    /**
     * Hent arrangementet for denne bloggen
     *
     * @return Arrangement
     * @throws Exception
     */
    public static function getArrangement() {
        if( !get_option('pl_id')) {
            throw new Exception(
                'Kan ikke kjøre getArrangement() på en blogg som ikke tilhører et arrangement',
                173001
            );
        }
        if( is_null( static::$arrangement ) ) {
            static::$arrangement = new Arrangement( intval(get_option('pl_id')));
        }
        return static::$arrangement;
    }
    
    /**
     * Get Flashbag 
     * Instance of UKMflashbag.class
     * 
     * @return Flashbag
     */
    public static function getFlash() {
        return static::$flashbag;
    }

    /**
     * Get Flashbag
     * ALIAS: static::getFlash()
     * 
     * @return Flashbag
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

    public static function getPluginUrl() {
        return plugin_dir_url( static::getPluginPath().'dummyfile' );
    }

    /**
     * Render admin-GUI
     */
    public static function renderAdmin() {
        try {

            static::addViewData(
                'action',
                static::getAction()
            );

			// Håndter lagring før visning
			if( $_SERVER['REQUEST_METHOD'] == 'POST') {
                static::setupLogger();
                if( isset( $_GET['save'] ) ) {
                    static::save( $_GET['save'] );
                }
			}
			
			## ACTION CONTROLLER
            static::includeActionController();
            
            ## RENDER
            echo TWIG( static::getAction() .'.html.twig', static::getViewData() , static::getPath(), true);

            // Hvis modulen bruker TwigJS
            if( file_exists( static::getTwigJsPath() ) ) {
                require_once('UKM/inc/twig-js.inc.php');
                echo TWIGjs_simple( static::getTwigJsPath() );
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
        static::$view_data['plugin_url'] = static::getPluginUrl();
        static::$view_data['flashbag'] = static::getFlashbag();
        static::$view_data['is_super_admin'] = is_super_admin();
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
     * Husk å sette opp ajax-hook i modul-filen
     * 
     * POST: 
     *  - action: {$classname}_ajax
     *  - controller: {filename}
     *  - module: (optional) subfolder of ajax
     * 
     * file_location: /ajax/{$controller}.ajax.php
	 *
	 * @return void
	 */
	public static function ajax() {
        header('Content-Type: application/json');

		if( is_array( $_POST ) ) {
			static::addResponseData('POST', $_POST );
		}
		
		try {
			static::setupLogger();

			$controller = basename( $_REQUEST['controller'] );
			if( $controller == 'save' ) {
				$controller = 'save/'. basename( $_POST['save'] );
            }
            
            if( isset( $_POST['module'] ) ) {
                $controller = basename( $_POST['module'] ) .'/'. $controller;
            }

			static::require('ajax/'. $controller .'.ajax.php');
		} catch( Exception $e ) {
			static::addResponseData('success', false);
			static::addResponseData('message', $e->getMessage() );
			static::addResponseData('code', $e->getCode() );
		}
		
		$data = json_encode( static::getResponseData() );
		echo $data;
		die();
	}	

    /**
     * Hent alle ajax response-data
     *
     * @return Array
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
     * @param String|Array key eller [key => val]
     * @param Null|Array data hvis oppgitt key som string
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
	 * @param String $file_path_in_plugin_dir
	 * @return void
	 */
    public static function require( $file ) {
        if( strpos( $file, 'UKM/' ) === 0 ) {
            require_once( $file );
        } else {
            require_once( static::getPluginPath() . $file );
        }
	}

	/**
	 * Include a file from the plugin directory if it exists
	 *
	 * @param String $file_path_in_plugin_dir
	 * @return void
	 */
    public static function include( $file ) {
        if( strpos( $file, 'UKM/' ) !== 0 ) {
            $file = static::getPluginPath() . $file;
        }
        if( file_exists( $file ) ) {
            include_once( $file );
        }
    }
    
    /**
     * Include the current action controller
     *
     * @return void
     */
    public static function includeActionController() {
        static::include('controller/'. static::getAction() .'.controller.php');
    }

    /**
     * Set up logger for current user
     *
     * @return void
     */
	public static function setupLogger() {
		## SETUP LOGGER
		global $current_user;
		get_currentuserinfo();
		Logger::setID( 'wordpress', $current_user->ID, get_option('pl_id') );
	}
}