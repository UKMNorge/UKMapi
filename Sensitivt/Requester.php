<?php

namespace UKMNorge\Sensitivt;

use Exception;

class Requester {

    private $system = null;
    private $id = null;
    private $ip = null;
    private $monstring_id = null;

    public function __construct( $system, $user_id, $monstring_id ) {
        $this->_confirmMonstringId( $monstring_id );
        $this->_confirmSystem( $system, $user_id );

        $this->system = $system;
        $this->id = $user_id;
        $this->monstring_id = $monstring_id;
        $this->ip = isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ? 
            $_SERVER['HTTP_CF_CONNECTING_IP'] : 
            $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Har SensitivtRequester oppgitt gyldig ID
     *
     * @throws Exception hvis mangler system, id eller ip
     * @return bool true
     */
    public function isReady() {
        if( null == $this->system || null == $this->id || null == $this->ip || null == $this->monstring_id ) {
            throw new Exception(
                'SensitivtRequester er ikke klar, eller har fått ugyldig data',
                118004
            );
        }
        return true;
    }


    /**
     * Bekreft at gitt wordpress data er gyldig
     *
     * @param Int $id
     * @throws Exception hvis gitt ugyldig id
     * @return bool true
     */
    private function _confirmWordpress( $id ) {
        if( !is_numeric( $id ) ) {
            throw new Exception(
                'SensitivtRequester:Wordpress krever numerisk ID',
                118002
            );
        }
        if( $id < 1 ) {
            throw new Exception(
                'SensitivtRequester:Wordpress krever numerisk ID større enn null',
                118003
            );
        }

        return true;
    }

    /**
     * Bekreft at gitt $monstring_id er gyldig
     *
     * @param Int $monstring_id
     * @throws Exception hvis gitt ugyldig monstring_id
     * @return bool true
     */
    private function _confirmMonstringId( $monstring_id ) {
        if( !is_numeric( $monstring_id ) ) {
            throw new Exception(
                'SensitivtRequester krever numerisk ID',
                118005
            );
        }
        if( $monstring_id < 1 ) {
            throw new Exception(
                'SensitivtRequester krever numerisk ID større enn 1',
                118006
            );
        }
        return true;
    }

    /**
     * Bekreft at gitt system-ID er støttet og har tilgang til sensitiv info
     *
     * @param String $system
     * @param Int $user_id
     * @throws Exception hvis gitt ukjent / ugyldig system
     * @return bool true
     */
    private function _confirmSystem( $system, $user_id ) {
        switch( $system ) {
            case 'wordpress':
                $this->_confirmWordpress( $user_id );
            break;
        
            default:
                throw new Exception(
                    'SensitivtRequester støtter ikke systemet '. $system,
                    118001
                );
        }
        return true;
    }

    /**
     * Hent gitt brukerID
     *
     * @return Int $user_id
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Hent gitt brukerID-system
     *
     * @return String $system
     */
    public function getSystem() {
        return $this->system;
    }

    /**
     * Hent aktiv IP-adresse
     *
     * @return String $ip_address
     */
    public function getIp() {
        return $this->ip;
    }

    /**
     * Hent gitt mønstring-ID
     *
     * @return Int $monstring_id
     */
    public function getMonstringId() {
        return $this->monstring_id;
    }
}