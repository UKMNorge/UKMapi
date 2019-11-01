<?php

namespace UKMNorge\Wordpress;

use Exception;

class User
{
    /**
     * Wordpress bruker-id
     *
     * @var Int
     */
    private $id = null;

    /**
     * Wordpress brukernavn
     *
     * @var String
     */
    private $username = null;

    /**
     * Wordpress e-postadresse
     *
     * @var String $email
     */
    private $email = null;

    /**
     * Brukerens fornavn (wp user meta: first_name)
     *
     * @var String
     */
    private $first_name = null;

    /**
     * Brukerens etternavn (wp user meta: last_name)
     *
     * @var String
     */
    private $last_name = null;

    /**
     * Brukerens mobilnummer (wp user meta: phone_number)
     *
     * @var Int
     */
    private $phone = null;

    /**
     * Er brukeren aktiv (eller deaktivert)
     *
     * @param Int $wp_user_id
     * @return Bool
     */
    public static function erAktiv( Int $wp_user_id ) {
        return !get_user_meta($wp_user_id, 'disabled'); 
    }


    /**
     * Sjekk om brukernavnet er ledig i wordpress
     *
     * @param String $username
     * @return boolean
     */
    public static function isAvailableUsername(String $username)
    {
        return !username_exists($username);
    }

    /**
     * Sjekk om e-posten er ledig (altså ikke tilhører en eksisterende bruker)
     *
     * @param String $email
     * @return boolean
     */
    public static function isAvailableEmail(String $email)
    {
        return !email_exists($email);
    }

    /**
     * Sjekk om brukernavn og e-post er ledig i wordpress
     *
     * @param String $username
     * @param String $email
     * @return boolean
     */
    public static function isAvailable(String $username, String $email)
    {
        return static::isAvailableUsername($username) && static::isAvailableEmail($email);
    }

    /**
     * Last inn wordpress-bruker fra e-post
     *
     * @param String $email
     * @return User $user
     */
    public static function loadByEmail(String $email)
    {
        if (static::isAvailableEmail($email)) {
            throw new Exception(
                'Kan ikke laste inn WP-bruker fra e-post, når e-post ikke finnes i databasen',
                171001
            );
        }

        $wpUser = get_user_by('email', $email);
        if (!$wpUser) {
            throw new Exception(
                'En feil oppsto ved innlasting av bruker fra e-postadresse',
                171002
            );
        }
        return new User($wpUser->ID);
    }

    /**
     * Hent bruker fra wordpress-ID
     *
     * @param Int $id
     * @return User
     * @throws Exception
     */
    public static function loadById( Int $id ) {
        $wpUser = get_user_by('id', $id);
        if (!$wpUser) {
            throw new Exception(
                'En feil oppsto ved innlasting av bruker fra ID',
                171004
            );
        }
        return new User($wpUser->ID);
    }

    /**
     * Opprett en bruker
     * Lagrer ikke brukeren til databasen, men oppretter et tomt objekt som senere
     * kan sendes til User::save( $user );
     *
     * @param String $username
     * @param String $email
     * @param String $first_name
     * @param String $last_name
     * @param Int $phone
     * @return User $user
     */
    public static function create(String $username, String $email, String $first_name, String $last_name, Int $phone)
    {
        $user = static::createEmpty();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setFirstName($first_name);
        $user->setLastName($last_name);
        $user->setPhone($phone);

        return $user;
    }

    /**
     * Opprett et placeholder-objekt, slik at man kan bruke støtte-
     * funksjoner i view osv
     *
     * @return User $user
     */
    public static function createEmpty()
    {
        $user = new User(0, false);
        return $user;
    }

    /**
     * Hent inn bruker-data fra standalone-miljø
     * (MEGET begrenset funksjonaltet da vi er uten WP-funksjoner)
     *
     * @param Int $id
     * @return User
     */
    public static function loadByIdInStandaloneEnvironment( Int $id ) {
        $user = new User($id,false);
        return $user;
    }


    /**
     * Opprett et brukerobjekt
     * Laster kun fra databasen hvis load == true
     *
     * @param Int $id
     * @param Bool $load
     */
    public function __construct(Int $id, Bool $load = true)
    {
        $this->id = $id;

        if (false && $id == 0) {
            throw new Exception(
                'Kan ikke laste inn bruker med ID==0',
                171003
            );
        }

        if ($load) {
            $data = get_user_by('ID', $id);

            $this->setEmail((string) $data->data->user_email);
            $this->setUsername((string) $data->data->user_login);
            $this->setFirstName((string) get_user_meta($data->ID, 'first_name', true));
            $this->setLastName((string) get_user_meta($data->ID, 'last_name', true));
            $this->setPhone((int) get_user_meta($data->ID, 'user_phone', true));
        }
    }


    /**
     * Finnes brukeren i databasen, eller er dette et placeholder-objekt?
     *
     * @return Bool
     */
    public function isReal()
    {
        return $this->getId() !== 0;
    }


    /**
     * Get wordpress user id
     *
     * @return  Int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set wordpress user id
     *
     * @param  Int  $id  Wordpress user id
     *
     * @return  self
     */
    public function setId(Int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get wordpress username
     *
     * @return  String
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Hent brukernavn (wordpress)
     *
     * @return String $brukernavn
     */
    public function getBrukernavn() {
        return $this->getUsername;
    }

    /**
     * Set wordpress username
     *
     * @param  String  $username  Wordpress username
     *
     * @return  self
     */
    public function setUsername(String $username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get $email
     *
     * @return  String
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Hent brukerens epost
     *
     * @return String $epost
     */
    public function getEpost() {
        return $this->getEmail();
    }

    /**
     * Set $email
     *
     * @param  String  $email  $email
     *
     * @return  self
     */
    public function setEmail(String $email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get user firstname (wp user meta)
     *
     * @return  String
     */
    public function getFirstName()
    {
        return $this->first_name;
    }
    /**
     * Hent brukerens fornavn
     *
     * @return String $fornavn
     */
    public function getFornavn() {
        return $this->getFirstName();
    }

    /**
     * Set user firstname (wp user meta)
     *
     * @param  String  $first_name  User firstname (wp user meta)
     *
     * @return  self
     */
    public function setFirstName(String $first_name)
    {
        $this->first_name = $first_name;

        return $this;
    }

    /**
     * Get user last name (wp user meta)
     *
     * @return  String
     */
    public function getLastName()
    {
        return $this->last_name;
    }
    /**
     * Hent brukerens etternavn
     *
     * @return String $etternavn
     */
    public function getEtternavn() {
        return $this->getLastName();
    }

    /**
     * Set user last name (wp user meta)
     *
     * @param  String  $last_name  User last name (wp user meta)
     *
     * @return  self
     */
    public function setLastName(String $last_name)
    {
        $this->last_name = $last_name;

        return $this;
    }

    /**
     * Get user phone number (wp user meta)
     *
     * @return  Int
     */
    public function getPhone()
    {
        return $this->phone;
    }
    /**
     * Hent brukerens telefonnummer
     *
     * @return Int $mobil
     */
    public function getTelefon() {
        return $this->getPhone();
    }
    /**
     * Hent brukerens telefonnummer
     * @see getTelefon()
     *
     * @return Int $mobil
     */
    public function getMobil() {
        return $this->getPhone();
    }

    /**
     * Set user phone number (wp user meta)
     *
     * @param  Int  $phone  User phone number (wp user meta)
     *
     * @return  self
     */
    public function setPhone(Int $phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get user full name
     *
     * @return String concat getFirstname() + ' ' + getLastname()
     */
    public function getName()
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    /**
     * Hent brukerens fulle navn
     *
     * @return String $navn
     */
    public function getNavn()
    {
        return $this->getName();
    }
}
