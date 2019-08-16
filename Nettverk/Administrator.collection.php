<?php

namespace UKMNorge\Nettverk;

use UKMNorge\Nettverk\Administrator;
use SQL;
use Exception;

require_once('UKM/Nettverk/Administrator.class.php');

class Administratorer {

    private $id = null;
    private $geo_type = null;
    private $geo_id = null;
    private $admins = [];

    public function __construct( $geo_id, $geo_type ) {
        $this->geo_id = $geo_id;
        $this->geo_type = $geo_type;
    }

    public static function getLoadQuery() {
        return "SELECT `wp_user_id` FROM `ukm_nettverk_admins`";
    }

    private function _load() {
        $sql = new SQL( static::getLoadQuery() . "
            WHERE `geo_type` = '#geo_type'
            AND `geo_id` = '#geo_id'",
            [
                'geo_type' => $this->getGeoType(),
                'geo_id' => $this->getGeoId()
            ]
        );
        $res = $sql->run();
        while( $r = SQL::fetch( $res ) ) {
            $user = new Administrator( (Int) $r['wp_user_id'] );
            $this->admins[ $user->getId() ] = $user;
        }
    }

    public function get( Int $id ) {
        foreach( $this->getAll() as $admin ) {
            if( $admin->getId() == $id ) {
                return $admin;
            }
        }
        throw new Exception(
            'Admin '. $id .' er ikke admin for '. $this->getNavn(),
            161001
        );
    }

    public function fjern( Int $id ) {
        if( isset( $this->admins[ $id ] ) ) {
            unset( $this->admins[ $id ] );
        }
        return true;
    }

    public function getAll() {
        if( empty( $this->admins ) ) {
            $this->_load();
        }
        return $this->admins;
    }

    /**
     * Get the value of geo_id
     */ 
    public function getGeoId()
    {
        return $this->geo_id;
    }

    /**
     * Get the value of geo_type
     */ 
    public function getGeoType()
    {
        return $this->geo_type;
    }

    /**
     * Get the value of id
     */ 
    public function getId()
    {
        return $this->id;
    }

    public function getNavn() {
        return $this->getGeoType() .' '. $this->getGeoId();
    }
}