<?php

namespace UKMNorge\Nettverk;

use Exception;
use UKMNorge\Arrangement\Kontaktperson\Kontaktperson;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Nettverk\Proxy\Kontaktperson as KontaktpersonProxy;
use UKMNorge\Wordpress\Blog;
use UKMNorge\Wordpress\User;

require_once('UKM/Autoloader.php');

class Administrator
{
    private $wp_user_id = 0;
    private $user = null;
    private $omrader = null;
    private $kontakt_synlighet = [];

    /**
     * Nytt Administrator-objekt
     *
     * @param Int $wp_user_id
     * @return self
     */
    public function __construct( Int $wp_user_id)
    {
        $this->wp_user_id = $wp_user_id;
    }

    /**
     * Last inn brukerdata
     *
     * @return void
     */
    private function _load()
    {        
        $this->user = new User( $this->getId() );
    }

    /**
     * Hent bruker-objektet 
     * 
     * @return User $user
     */
    public function getUser()
    {
        if ($this->user == null) { 
            $this->_load();
        }
        return $this->user;
    }

    /**
     * Er administratoren også en kontaktperson for området?
     *
     * @return Bool
     */
    public function erKontaktperson( Omrade $omrade ) {
        if( !isset( $this->kontakt_synlighet[ $omrade->getId() ] ) ) {
            $this->loadKontaktpersonSynlighet( $omrade );
        }
        return $this->kontakt_synlighet[ $omrade->getId() ];
    }

    /**
     * Hent kontaktperson-objektet (eller proxy)
     *
     * @throws Exception
     * @return Kontaktperson|KontaktpersonProxy
     */
    public function getKontaktperson() {
        try {
            return Kontaktperson::getByAdminId($this->getId());
        } catch (Exception $e) {
            if ($e->getCode() != 111001) {
                throw $e;
            }
            return new KontaktpersonProxy($this);
        }
    }

    /**
     * Angi om administratoren er kontaktperson for gitt område
     *
     * @param Omrade $omrade
     * @param Bool $synlig
     * @return self
     */
    public function setKontaktpersonSynlighet( Omrade $omrade, Bool $synlig) {
        $this->kontakt_synlighet[ $omrade->getId() ] = $synlig;
        return $this;
    }

    /**
     * Hent fra database hvorvidt administratoren er kontakt for gitt område
     *
     * @param Omrade $omrade
     * @return self
     */
    private function loadKontaktpersonSynlighet( Omrade $omrade ) {
        $query = new Query(
            "SELECT `is_contact`
            FROM `ukm_nettverk_admins`
            WHERE `wp_user_id` = '#userid'
                AND `geo_type` = '#geo_type'
                AND `geo_id` = '#geo_id'
            ",
            [
                'userid' => $this->getId(),
                'geo_type' => $omrade->getType(),
                'geo_id' => $omrade->getForeignId()
            ]
        );
        $this->kontakt_synlighet[ $omrade->getId() ] = $query->getField() == 'true';
        return $this;
    }

    /**
     * Hent brukerens ID (WP_User::ID)
     * 
     * @return Int $id
     */ 
    public function getId()
    {
        return $this->wp_user_id;
    }

    /**
     * Hent administratorens navn
     *
     * @return String $navn
     */
    public function getNavn() {
        return $this->getUser()->getNavn();
    }

    /**
     * Hent antall områder (av gitt type) administratoren har tilgang til
     *
     * @param String $type
     * @return Int $antall_omrader
     */
    public function getAntallOmrader( String $type=null) {
        return sizeof( $this->getOmrader( $type ) );
    }

    /**
     * Har administratoren noen områder (for gitt type)
     *
     * @param String $type
     * @return Bool
     */
    public function erAdmin( $type=null ) {
        return $this->getAntallOmrader( $type ) > 0;
    }

    /**
     * Har tilgang til blogg på gitt path?
     *
     * @see harTilgangTilBlogId()
     * @param String $path
     * @return Bool
     */
    public function harTilgangTilBlog( String $path ) {
        return $this->harTilgangTilBlogId( Blog::getIdByPath($path));
    }

    /**
     * Har tilgang til blogg med gitt ID?
     *
     * @param Int $id
     * @return Bool
     */
    public function harTilgangTilBlogId( Int $id ) {
        $blogs = get_blogs_of_user( $this->getUser()->getId() );
        foreach( $blogs as $blog ) {
            #echo "\r\n". (Int) $blog->userblog_id .' == '. $id .' => '. ((Int) $blog->userblog_id == $id ? 'true' : 'false') .'<br />';
            if( (Int) $blog->userblog_id == $id ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Hent det ene området administratoren har tilgang til
     *
     * @param String $type
     * @return Omrade
     */
    public function getOmrade(String $type=null) {
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
     * @param String $type
     * @return Array<Omrade>
     */
    public function getOmrader(String $type=null ) {
        if( null == $this->omrader ) {
            $this->_loadOmrader();
        }
        if( null === $type ) {
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

    /**
     * Last inn områder administratoren har tilgang til
     *
     * @return Array<Omrade>
     */
    private function _loadOmrader() {
        $sql = new Query("SELECT * 
            FROM `ukm_nettverk_admins`
            WHERE `wp_user_id` = '#userid'",
            [
                'userid' => $this->getId()
            ]
        );
        $res = $sql->run();

        $this->omrader = [];
        while( $row = Query::fetch( $res ) ) {
            $omrade = new Omrade( $row['geo_type'], (Int) $row['geo_id'] );
            $this->omrader[ $omrade->getId() ] = $omrade;
        }
    }
}
