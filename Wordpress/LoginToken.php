<?php

namespace UKMNorge\Wordpress;

use \Exception;
use \DateTime;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;

Class LoginToken {
    var $id = null;
    var $delta_id = null;
    var $secret = null;
    var $wp_id = null;
    var $timestamp = null;

    /**
     * Opprett et LoginTokenObjekt
     *
     * @param Array $data
     */
    public function __construct( Array $data ) {
        $this->token_id = $data['token_id'];
        $this->delta_id = $data['delta_id'];
        $this->secret = $data['secret'];
        $this->wp_id = $data['wp_id'];
        $this->timestamp = new DateTime($data['timestamp']);
        $this->used = $data['used'];
    }

    /**
     * Opprett en token for gitt bruker
     *
     * @param Int $delta_id
     * @param Int $wp_id
     * @return LoginToken $logintoken
     */
    public static function create( Int $delta_id, Int $wp_id ) {
        # Nekt å opprett token for wp_id = 1, da dette er superbrukeren!
        if( $wp_id == 1 || $wp_id == NULL ) {
            throw new Exception("Du kan ikke logge inn som denne brukeren.");
        }
        
        $insert = new Insert('ukm_delta_wp_login_token');
        $insert->add('delta_id', $delta_id);
        $insert->add('wp_id', $wp_id);
        $insert->add('secret', sha1( $delta_id . UKM_SALT . microtime() ));
        
        $res = $insert->run();
        
        if( !$res ) {
            throw new Exception(
                'Kunne ikke opprette LoginToken',
                571004
            );
        }

        return static::loadById( $res );
    }

    /**
     * 
     */
    public static function loadById( Int $id ) {
        $sql = new Query("SELECT * FROM `ukm_delta_wp_login_token` WHERE `token_id` = '#id'", ['id' => $id]);
        $res = $sql->getArray();
        if( false == $res ) {
            throw new Exception ("Did not find any token with id ".$id);
        }
        return new LoginToken( $res );
    }

    /**
     * Last inn en loginToken fra ID og hemmelighet
     *
     * @param Int $id
     * @return void
     */
    private static function get( Int $id, String $secret ) {
        $query = new Query(
            "SELECT * 
            FROM `ukm_delta_wp_login_token`
            WHERE `token_id` = #id
            AND `secret` = '#secret'
            AND `timestamp` > (NOW() - INTERVAL 1 MINUTE)
            AND `used` = 'false'
            ",
            [
                'id' => $id,
                'secret' => $secret
            ]
        );
        $res = $query->getArray();

        if( !$res ) {
            throw new Exception(
                'Fant ikke loginToken '. $id,
                571005
            );
        }
        return new LoginToken( $res );
    }

    /**
     * Bruk en token for å logge inn
     *
     * @param Int $id
     * @param String $secret
     * @return Int $wordpressId
     */
    public static function use( Int $id, String $secret ) {
        $token = static::get($id, $secret);

        $update = new Update(
            'ukm_delta_wp_login_token',
            [
                'token_id' => $id
            ]
        );
        $update->add('used','true');
        $res = $update->run();
        if( !$res ) {
            throw new Exception(
                'Kunne ikke bruke token '. $id,
                571006
            );
        }
        return $token->wp_id;
    }
}