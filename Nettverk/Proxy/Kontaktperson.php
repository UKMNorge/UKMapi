<?php

namespace UKMNorge\Nettverk\Proxy;

use UKMNorge\Arrangement\Kontaktperson\KontaktInterface;
use UKMNorge\Nettverk\Administrator;
use UKMNorge\Wordpress\User;

class Kontaktperson implements KontaktInterface {
    private $admin;

    /**
     * Opprett ny KontaktpersonProxy
     *
     * @param Administrator $admin
     */
    public function __construct( Administrator $admin ) {
        $this->admin = $admin;
    }

    public function getId() {
        return $this->_getUser()->getId();
    }

    /**
     * Hent administratorens bruker-element
     *
     * @return User
     */
    private function _getUser() {
        return $this->admin->getUser();
    }

    /**
     * Hent brukerens navn
     *
     * @return String $navn
     */
    public function getNavn() {
        return $this->_getUser()->getNavn();
    }
    /**
     * Hent brukerens fornavn
     *
     * @return String
     */
    public function getFornavn() {
        return $this->_getUser()->getFornavn();
    }
    /**
     * Hent brukerens etternavn
     *
     * @return String
     */
    public function getEtternavn()
    {
        return $this->_getUser()->getEtternavn();
    }
    /**
     * Hent brukerens telefonnummer
     *
     * @return Int
     */
    public function getTelefon()
    {
        return $this->_getUser()->getMobil();
    }
    /**
     * Hent brukerens e-post
     *
     * @return String
     */
    public function getEpost()
    {
        return $this->_getUser()->getEpost();
    }   

    /**
     * Hent brukerens facebook-lenke
     * (vil alltid returnere null)
     *
     * @return null
     */
    public function getFacebook() {
        return null;
    }
}