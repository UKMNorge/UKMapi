<?php

namespace UKMNorge\Nettverk;
use UKMNorge\Wordpress\User;
use SQL;

require_once('UKM/Autoloader.php');

class Administrator
{

    private $wp_user_id = 0;
    private $user = null;
    private $omrader = null;

    public function __construct( Int $wp_user_id)
    {
        $this->wp_user_id = $wp_user_id;
    }


    private function _load()
    {        
        $this->user = new User( $this->getId() );
    }

    /**
     * Get the value of user
     */
    public function getUser()
    {
        if ($this->user == null) { 
            $this->_load();
        }
        return $this->user;
    }

    /**
     * Get the value of wp_user_id
     */ 
    public function getId()
    {
        return $this->wp_user_id;
    }

    public function getNavn() {
        return $this->getUser()->getNavn();
    }

    public function getAntallOmrader( $type=false) {
        return sizeof( $this->getOmrader( $type ) );
    }

    public function erAdmin( $type=false ) {
        return $this->getAntallOmrader( $type ) > 0;
    }

    public function getOmrade($type=false) {
        if( $this->getAntallOmrader($type) != 1 ) {
            throw new Exception(
                'UKMNorge\Nettverk\Omrade::getOmrade() kan kun brukes når '.
                'admin har rettigheter til ett område.',
                161002
            );
        }
        
        return array_shift(array_values( $this->getOmrader($type) ) );
    }

    /**
     * Hent alle områder admin har tilgang til
     *
     * @return Array [Omrade]
     */
    public function getOmrader( $type=false ) {
        if( null == $this->omrader ) {
            $this->_loadOmrader();
        }
        if( !$type ) {
            return $this->omrader;
        }
        $filtered_omrader = [];
        foreach( $this->omrader as $omrade ) {
            if( $omrade->getType() == $type ) {
                $filtered_omrader[ $omrade->getId() ] = $omrade;
            }
        }
        return $filtered_omrader;
    }

    private function _loadOmrader() {
        $sql = new SQL("SELECT * 
            FROM `ukm_nettverk_admins`
            WHERE `wp_user_id` = '#userid'",
            [
                'userid' => $this->getId()
            ]
        );
        $res = $sql->run();

        $this->omrader = [];
        while( $row = SQL::fetch( $res ) ) {
            $omrade = new Omrade( $row['geo_type'], (Int) $row['geo_id'] );
            $this->omrader[ $omrade->getId() ] = $omrade;
        }
    }
}
