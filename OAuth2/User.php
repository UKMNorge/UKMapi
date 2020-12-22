<?php

namespace UKMNorge\OAuth2;

ini_set("display_errors", true);

use Exception;

// Representasjon av en bruker for autentisering
class User {

    private $user_id;
    private $tel_nr;
    private $first_name;
    private $last_name;
    private $tel_nr_verified;

    protected $notFound = true;
    protected static $storage;

    public function __construct($tel_nr) {
        $this->setTelNumber($tel_nr);
        static::$storage = ServerMain::getStorage();
        $this->load();
    }

    public function setTelNumber(string $tel_nr) {
        if(preg_match("/^[++]{1}47[0-9]{8}$/", $tel_nr) == 0) {
            throw new Exception("Telefonnummer er ikke gyldig, format: +47XXXXXXXX", 583001);
        }

        $this->tel_nr = $tel_nr;
    }

    public function setFirstName(string $first_name) {
        $this->first_name = $first_name;
    }

    public function setLastName(string $last_name) {
        $this->last_name = $last_name;
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


    public function getTelNr() : string {
        return $this->tel_nr;
    }

    public function getFirstName() : string {
        return $this->first_name;
    }

    public function getLastName() : string {
        return $this->last_name;
    }

    public function isTelNrVerified() : bool {
        return $this->tel_nr_verified;
    }

    // This must be private and called by constructor only
    private function load() {
        $userArr = static::$storage->getUser($this->tel_nr); // Throws exception if user is not found!
        
        $this->user_id = $userArr['user_id'];
        $this->first_name = $userArr['first_name'];
        $this->last_name = $userArr['last_name'];
        $this->tel_nr_verified = $userArr['tel_nr_verified'] == "1";
        
    }

    public function save() {
        static::$storage->updateUser($this);
    }
    
}