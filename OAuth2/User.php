<?php

namespace UKMNorge\OAuth2;

ini_set("display_errors", true);

use Exception;
use Datetime;

// Representasjon av en bruker for autentisering
class User {

    private $user_id;
    private $tel_country_code;
    private $tel_nr;
    private $first_name;
    private $last_name;
    private $birthday;
    private $tel_nr_verified;
    private $vilkaar;

    protected $notFound = true;
    protected static $storage;

    public function __construct($tel_nr) {
        $this->setTelNumber($tel_nr);
        static::$storage = ServerMain::getStorage();
        $this->load();
    }

    public function reloadUser() {
        $this->load();
    }

    // Støtter bare Norge for nå
    // Om systemet skal utvides til å støtte flere land, da skal country code brukes som del av autentisering 
    public function setTelCountryCode() {
        $this->tel_country_code = "+47";
    }

    public function setTelNumber(string $tel_nr) {
        $this->tel_nr = $tel_nr;
    }

    public function setFirstName(string $first_name) {
        $this->first_name = $first_name;
    }

    public function setLastName(string $last_name) {
        $this->last_name = $last_name;
    }
    
    public function setBirthday(DateTime $birthday) {
        $this->birthday = $birthday;
    }
    
    public function changePassword(string $password) : bool {
        return static::$storage->changePassword($this, $password);
    }

    public function setTelNrVerified() : bool {
        if($this->tel_nr_verified) {
            throw new Exception("Telefonnummer er allerede verifisert!", 583002);
        }
        return static::$storage->setVerifiedTelNr($this);
    }

    public function setVilkaarToAccepted() {
        $this->vilkaar = true;
    }


    public function getTelCountryCode() : string {
        return $this->tel_country_code;
    }

    public function getTelNr() : string {
        return $this->tel_nr;
    }

    public function getFirstName() : string {
        return $this->first_name;
    }

    public function getLastName() : string {
        return $this->last_name;
    }

    public function getBirthday() : DateTime {
        return $this->birthday;
    }

    public function isTelNrVerified() : bool {
        return $this->tel_nr_verified;
    }

    public function isVilkaarAccepted() : bool {
        return $this->vilkaar;
    }

    // This must be private and called by constructor only
    private function load() {
        $userArr = static::$storage->getUser($this->tel_nr); // Throws exception if user is not found!

        $this->user_id = $userArr['user_id'];
        $this->tel_country_code = $userArr['tel_country_code'];
        $this->first_name = $userArr['first_name'];
        $this->last_name = $userArr['last_name'];
        $this->birthday = new DateTime($userArr['birthday']);
        $this->tel_nr_verified = $userArr['tel_nr_verified'] == "1";
        $this->vilkaar = $userArr['vilkaar'] == "1";
    }

    public function save() {
        static::$storage->updateUser($this);
    }
    
}