<?php

namespace UKMNorge\Nettverk;

use Exception;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class Administratorer {

    private $type = null;
    private $id = 0;
    private $admins = [];

    /**
     * Ny administrator-collection
     *
     * @param String $geo_type
     * @param Int $geo_id
     * @return self
     */
    public function __construct( String $geo_type, Int $geo_id ) {
        $this->type = $geo_type;
        $this->id = $geo_id;
    }

    /**
     * Hent SQL-spørringens start
     * 
     * Sikrer standardisert felt-selector hvis den kjøres fra andre steder
     *
     * @return String $sql
     */
    public static function getLoadQuery() {
        return "SELECT `wp_user_id` FROM `ukm_nettverk_admins`";
    }

    /**
     * Last inn administratorer for området
     *
     * @return void
     */
    private function _load() {
        $sql = new Query( static::getLoadQuery() . "
            WHERE `geo_type` = '#geo_type'
            AND `geo_id` = '#geo_id'",
            [
                'geo_type' => $this->getType(),
                'geo_id' => $this->getId()
            ]
        );
        $res = $sql->run();
        while( $r = Query::fetch( $res ) ) {
            $user = new Administrator( (Int) $r['wp_user_id'] );
            $this->admins[ $user->getId() ] = $user;
        }
    }

    /**
     * Hent en gitt administrator
     *
     * @param Int $id
     * @return Administrator
     * @throws Exception ikke funnet
     */
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

    /**
     * Fjern en administrator
     *
     * @param Int $id
     * @return true
     */
    public function fjern( Int $id ) {
        if( isset( $this->admins[ $id ] ) ) {
            unset( $this->admins[ $id ] );
        }
        return true;
    }

    /**
     * Hent alle administratorer
     *
     * @return Array<Administrator>
     */
    public function getAll() {
        if( empty( $this->admins ) ) {
            $this->_load();
        }
        return $this->admins;
    }

    /**
     * Hent antall administratorer
     *
     * @return Int $antall
     */
    public function getAntall() {
        return sizeof( $this->getAll() );
    }

    /**
     * Hent områdets ID
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent type område
     */ 
    public function getType()
    {
        return $this->type;
    }

    /**
     * Hent navn på område
     *
     * @return String
     */
    public function getNavn() {
        return $this->getType() .' '. $this->getId();
    }
}