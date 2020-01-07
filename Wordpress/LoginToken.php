<?php

namespace UKMNorge\Wordpress;

use DateTime;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;

Class LoginToken {
    var $id = null;
    var $delta_id = null;
    var $wp_id = null;
    var $timestamp = null;

    /**
     * Opprett et LoginTokenObjekt
     *
     * @param Array $data
     */
    public function __construct( Array $data ) {
        $this->id = $data['token_id'];
        $this->delta_id = $data['delta_id'];
        $this->wp_id = $data['wp_id'];
        $this->timestamp = new DateTime($data['timestamp']);
    }

    /**
     * Opprett en token for gitt bruker
     *
     * @param Int $delta_id
     * @param Int $wp_id
     * @return LoginToken $logintoken
     */
    public static function create( Int $delta_id, Int $wp_id ) {
        $insert = new Insert('ukm_delta_wp_login_token');
        $insert->add('delta_id', $delta_id);
        $insert->add('wp_id', $wp_id);
        $insert->add('secret', sha1( $delta_id . UKM_SALT));
        
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
     * Last inn en loginToken fra ID og hemmelighet
     *
     * @param Int $id
     * @return void
     */
    private static function get( Int $id, String $secret ) {
        $query = new Query(
            "SELECT * 
            FROM `ukm_delta_wp_login_token`
            WHERE `delta_id` = '#id'
            AND `secret` = '#secret'
            AND `timestamp` < (NOW() - INTERVAL 1 MINUTE)
            ",
            [
                'delta_id' => $id,
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
                'id' => $token->token_id
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