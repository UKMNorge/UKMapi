<?php

namespace UKMNorge\Wordpress;

use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Typer\Type;
use UKMNorge\Meta\Collection;
use UKMNorge\Meta\Write;
use WP_User;

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

    private $meta = null;

    /**
     * Bilde av User. Kommer fra tabel 'wp_user_bilde' utenfor Wordpress verden
     *
     * @var String
     */
    private $bilde = null;

    /**
     * Er brukeren aktiv (eller deaktivert)
     *
     * @param Int $wp_user_id
     * @return Bool
     */
    public static function erAktiv(Int $wp_user_id)
    {
        return !get_user_meta($wp_user_id, 'disabled');
    }

    /**
     * Har brukeren tilgang til gitt blogg?
     *
     * @param Int $blog_id
     * @return Bool
     */
    public function harTilgangTilBlogg(Int $blog_id)
    {
        return Blog::harBloggBruker($blog_id, $this);
    }

    /**
     * Er brukeren en oppgradert deltaker-bruker?
     * 
     * Mediedeltakere er contributor til vanlig => blir author
     * Arrangører er ukm_produsent til vanlig => blir editor
     * 
     * @param Int $wp_user_id 
     * @param Int $blog_id
     * @return bool
     * @throws Exception
     */
    public static function erBrukerenOppgradert(Int $wp_user_id, Int $blog_id)
    {
        $wp_users = get_users(['blog_id' => $blog_id, 'search' => $wp_user_id]);
        if (!isset($wp_users[0])) {
            throw new Exception("Denne brukeren er ikke lagt til blogg " . $blog_id . "!", 171006);
        }
        $roles = $wp_users[0]->roles;
        if (!in_array('author', $roles) && !in_array('editor', $roles)) {
            return false;
        }
        return true;
    }

    /**
     * Dynamisk wrapper rundt erBrukerenOppgradert(xx).
     * Sånn at vi kan være lat i Twig.
     * 
     */
    public function erOppgradert()
    {
        return static::erBrukerenOppgradert($this->getId(), get_current_blog_id());
    }

    /**
     * Finner rollen en innslagstype skal ha i Wordpress
     * 
     * @param Type
     * @return String rolle - kan insertes i Wordpress-kall.
     * @throws Exception 
     */
    public static function getRolleForInnslagType(Type $type)
    {
        if ($type->getKey() == 'arrangor') {
            return 'ukm_produsent';
        } elseif ($type->getKey() == 'nettredaksjon') {
            return 'contributor';
        } else {
            throw new Exception("Denne innslagstypen skal ikke ha rettigheter til arrangørsystemet.", 171008);
        }
    }

    /**
     * Finner rollen en innslagstype skal ha i Wordpress når den oppgraderes.
     * 
     * @param Type
     * @return String rolle - kan insertes i Wordpress-kall.
     * @throws Exception dersom innslagstypen ikke skal ha rettigheter.
     */
    public static function getOppgradertRolleForInnslagType(Type $type)
    {
        if ($type->getKey() == 'arrangor') {
            return 'editor';
        } elseif ($type->getKey() == 'nettredaksjon') {
            return 'author';
        } else {
            throw new Exception("Denne innslagstypen skal ikke ha rettigheter til arrangørsystemet.", 171007);
        }
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
    public static function loadById(Int $id)
    {
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
     * Hent Instrato-nøkkel
     * Krever WP-context
     *
     * @return String
     */
    public function getInstratoKey()
    {
        return $this->getMeta()->getValue('instrato');
    }

    /**
     * Har brukeren en instrato-nøkkel?
     *
     * @return Bool
     */
    public function hasInstratoKey()
    {
        return !is_null($this->getInstratoKey());
    }

    /**
     * Generer en ny instratoKey
     *
     * @return void
     */
    public function generateInstratoKey()
    {
        $value = $this->getMeta()->get('instrato');
        $value->setValue(User::randomString(25));
        Write::set($value);
    }

    /**
     * Hent metadata-container
     *
     * @return Collection
     */
    public function getMeta()
    {
        if (null == $this->meta) {
            $this->meta = Collection::createByParentInfo('User', $this->getId());
        }
        return $this->meta;
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
    public static function loadByIdInStandaloneEnvironment(Int $id)
    {
        $user = new User($id, false);

        $query = new Query(
            "SELECT `user_email`,
            (SELECT `meta_value`
                FROM `wpms2012_usermeta`
                WHERE `user_id` = `user`.`ID`
                AND `meta_key` = 'first_name') AS `first_name`,
            (SELECT `meta_value`
                FROM `wpms2012_usermeta`
                WHERE `user_id` = `user`.`ID`
                AND `meta_key` = 'last_name') AS `last_name`
            FROM `wpms2012_users` AS `user`
            WHERE `user`.`ID` = '#userid'",
            [
                'userid' => $id
            ],
            'wordpress'
        );
        $data = $query->getArray();

        $user->setEmail($data['user_email']);
        $user->setFirstName($data['first_name']);
        $user->setLastName($data['last_name']);

        return $user;
    }

    /**
     * Hent inn WP user fra standalone-miljø via telefonnummer
     *
     * @param Int $id
     * @return User|null
     */
    public static function loadByPhoneInStandaloneEnvironment($phone) : User|null
    {
        $query = new Query(
            "SELECT 
                u.ID,
                u.user_email,
                um_phone.meta_value AS user_phone,
                um_fn.meta_value AS first_name,
                um_ln.meta_value AS last_name
            FROM wpms2012_users u
            LEFT JOIN wpms2012_usermeta um_phone 
                ON um_phone.user_id = u.ID AND um_phone.meta_key = 'user_phone'
            LEFT JOIN wpms2012_usermeta um_fn 
                ON um_fn.user_id = u.ID AND um_fn.meta_key = 'first_name'
            LEFT JOIN wpms2012_usermeta um_ln 
                ON um_ln.user_id = u.ID AND um_ln.meta_key = 'last_name'
            WHERE um_phone.meta_value = '#userPhone'",
            [
                'userPhone' => $phone
            ],
            'wordpress'
        );
        $data = $query->getArray();
        if(!$data || !isset($data['ID'])) {
            return null;
        }
        $user = new User($data['ID'], false);

        // Hent bilde
        $sql = new Query(
            "SELECT `bilde_url`
            FROM `wp_user_bilde`
            WHERE `wp_user` = '#userid'",
            [
                'userid' => $user->getId()
            ]
        );

        $row = $sql->run('array');
        if($row) {
            $user->bilde = $row['bilde_url'];
        }

        $user->setPhone($data['user_phone']);
        $user->setEmail($data['user_email']);
        $user->setFirstName($data['first_name']);
        $user->setLastName($data['last_name']);


        return $user;
    }

    /**
     * Henter bruker ut fra gitt participant_id
     *
     * @throws Exception not found
     * @param Int $p_id
     * @return User
     */
    public static function loadByParticipant(Int $p_id)
    {
        $query = new Query(
            "SELECT `wp_id`
            FROM `ukm_delta_wp_user` 
            WHERE `participant_id` = '#id'",
            [
                'id' => $p_id
            ]
        );
        $wp_id = (int) $query->getField();
        try {
            if (function_exists('get_user_by')) {
                $user = static::loadById($wp_id);
            } else {
                $user = User::loadByIdInStandaloneEnvironment($wp_id);
            }
        } catch (Exception $e) {
            throw new Exception(
                'Kunne ikke finne Wordpress-bruker for deltaker ' . $p_id . '. ' .
                    'Systemet sa: ' . $e->getMessage(),
                171005
            );
        }
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

            $this->loadBilde();
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
    public function getBrukernavn()
    {
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
    public function getEpost()
    {
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
    public function getFornavn()
    {
        return $this->getFirstName();
    }

    /**
     * Hent bilde url fra database
     *
     * @return void
     */
    private function loadBilde() {
        if(!$this->bilde) {
            $sql = new Query(
                "SELECT `bilde_url`
                FROM `wp_user_bilde`
                WHERE `wp_user` = '#userid'",
                [
                    'userid' => $this->getId()
                ]
            );

            $row = $sql->run('array');
            if($row) {
                $this->bilde = $row['bilde_url'];
            }
        }
    }

    /**
     * Hent bilde
     *
     * @return String
     */
    public function getBilde() {
        return $this->bilde;
    }

    /**
     * Set bilde
     *
     * @param String $bildeUrl
     * 
     * @return void
     */
    public function setBilde($bildeUrl) {
        $this->bilde = $bildeUrl;
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
    public function getEtternavn()
    {
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
    public function getTelefon()
    {
        return $this->getPhone();
    }
    /**
     * Hent brukerens telefonnummer
     * @see getTelefon()
     *
     * @return Int $mobil
     */
    public function getMobil()
    {
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

    /**
     * Hent deft faktiske wordpress-objektet (WPUser)
     *
     * @return WP_User
     */
    public function getWordpressObject() {
        return get_user_by('id', $this->getId());
    }

    /**
     * Generate a random string, using a cryptographically secure 
     * pseudorandom number generator (random_int)
     * @see https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
     * 
     * @param int $length      How many characters do we want?
     * @return string
     */
    public static function randomString(Int $length = 64)
    {
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        if ($length < 1) {
            throw new \RangeException("Length must be a positive integer");
        }

        $pieces = [];
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $pieces[] = $keyspace[random_int(0, $max)];
        }

        return implode('', $pieces);
    }
}
