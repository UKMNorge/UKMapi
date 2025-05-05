<?php

namespace UKMNorge\Innslag\Personer;

use Exception;
use DateTime;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Innslag\Context\Context;
use UKMNorge\Innslag\Typer\Typer;
use UKMNorge\Sensitivt\Person as PersonSensitivt;
use UKMNorge\Wordpress\User;
use UKMNorge\Tools\Sanitizer;

require_once('UKM/Autoloader.php');

class Person
{
    var $context = null;

    var $id = null;
    var $fornavn = null;
    var $etternavn = null;
    var $mobil = null;
    var $rolle = null;
    var $rolleObject = null;
    var $epost = null;
    var $fodselsdato = null;

    var $adresse = null;
    var $postnummer = null;
    var $poststed = null;

    private $sensitivt = null;
    var $attributes = null;

    var $pameldt_til = [];

    /**
     * Hent en person fra gitt ID
     *
     * @param Int $id
     * @return Person
     */
    public static function loadFromId(Int $id)
    {
        $person = new static($id);
        if (!$person) {
            throw new Exception(
                'Fant ikke person ' . $id,
                109005
            );
        }
        return $person;
    }

    /**
     * Hent person fra telefonnummer
     * 
     * OBS: Kan returnere null
     *
     * @param String $phone
     * @return Person|null
     */
    public static function loadFromPhone(String $phone)
    {
        $qry = new Query(
            self::getLoadQuery() . "
            WHERE `p_phone` = '#phone'",
            [
                'phone' => $phone
            ]
        );
        $person_data = $qry->run('array');
        if (!$person_data) {
            return null;
        }
        return new static($person_data);
    }


    /**
     * Hent fødselsdato ut fra en gitt alder
     *
     * @param Int $alder
     * @return DateTime
     */
    public static function getFodselsdatoFromAlder(Int $alder)
    {
        if ($alder == 0) {
            return null;
        }
        return new DateTime(((int) date('Y') - $alder) . '-01-01');
    }

    /**
     * Sett attributt
     *
     * Sett egenskaper som for enkelhets skyld kan følge personen et lite stykke
     * Vil aldri kunne lagres
     *
     * @param String $key
     * @param Any $value
     *
     * @return self
     **/
    public function setAttr(String $key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Hent attributt
     * 
     * @param String $key
     * @return Any value
     **/
    public function getAttr(String $key)
    {
        return $this->hasAttr($key) ? $this->attributes[$key] : false;
    }

    public function hasAttr(String $key) 
    {
        return isset($this->attributes[$key]);
    }

    /**
     * Standardisering av database-spørring for uthenting av personer
     *
     * @return void
     */
    public static function getLoadQuery()
    {
        return "SELECT * FROM `smartukm_participant` ";
    }

    /**
     * Hent person fra fornavn, etternavn og mobil
     *
     * @param String $fornavn
     * @param String $etternavn
     * @param String $mobil
     * @return void
     */
    public static function loadFromData(String $fornavn, String $etternavn, Int $mobil)
    {
        $qry = new Query(
            self::getLoadQuery() . "
			WHERE `p_firstname` = '#fornavn' 
			AND `p_lastname` = '#etternavn' 
			AND `p_phone` = '#mobil'",
            [
                'fornavn' => $fornavn,
                'etternavn' => $etternavn,
                'mobil' => $mobil
            ]
        );
        $person_data = $qry->run('array');

        if (!$person_data) {
            throw new Exception(
                'Beklager, fant ikke ' . $fornavn . ' ' . $etternavn . ' (' . $mobil . ')',
                109004
            );
        }

        return new static($person_data);
    }


    /**
     * Hent wordpressId for deltakeren (hvis de har en)
     *
     * @throws Exception if not found
     * @return Int $wordpressId
     */
    public function getWordpressId() {
        try {
            $user = User::loadByParticipant($this->getId());
        } catch( Exception $e ) {
            if( $e->getCode() == 171005 && !empty($this->getEpost())) {
                $user = User::loadByEmail( $this->getEpost());
            } else {
                throw $e;
            }
        }

        return $user->getId();
    }

    /**
     * Sjekk om personen har en wordpress-bruker
     * 
     * @return bool 
     */
    public function harWordpressBruker() {
        try {
            $this->getWordpressId();
            return true;
        } catch ( Exception $e ) {
            return false;
        }
    }
    
    /**
     * Hent Wordpress-brukeren til personen
     * 
     * @return User
     * @throws Exception
     */
    public function getWordpressBruker() {
        return new User( static::getWordpressId() );
    }
    /**
     * Hent Wordpress-brukeren til personen
     * 
     * @return User
     * @see getWordpressBruker()
     * @throws Exception
     */
    public function hentWordpressBruker() {
        return $this->getWordpressBruker();
    }

    /**
     * Sett hvilke arrangement-IDer tittelen er videresendt til
     *
     * @param Array<Int> ID
     * @return $this
     */
    public function setPameldt(Array $pameldt_til)
    {
        $this->pameldt_til = $pameldt_til;
        return $this;
    }

    

    /**
     * Legg til enda et arrangement hvor personen er påmeldt
     *
     * @param Int $pameldt_til
     * @return Bool true
     */
    public function addPameldt(Int $pameldt_til)
    {
        if (!in_array($pameldt_til, $this->pameldt_til)) {
            $this->pameldt_til[] = $pameldt_til;
        }
        return true;
    }
    
    /**
     * Hent hvilke arrangement-IDer personen er påmeldt
     * 
     * Gjelder også på lokalmønstring fra og med 2020
     * 
     * @return Array<Int> $pameldt_til
     **/
    public function getPameldt()
    {
        return $this->pameldt_til;
    }

    /**
     * Hent hvilke andre arrangement-IDer personen er påmeldt
     *
     * @param Int $arrangement_id
     * @return Array<Int>
     */
    public function getPameldtAndre( Int $arrangement_id ) {
        $pameldt_til = $this->getPameldt();
        if(($key = array_search($arrangement_id, $pameldt_til)) !== false ) {
            unset( $pameldt_til[$key] );
        }
        return $pameldt_til;
    }

    /**
     * Er påmeldt gitt mønstring?
     *
     * @param Int $arrangement_id
     * @return Bool
     **/
    public function erPameldt(Int $arrangement_id)
    {
        return in_array($arrangement_id, $this->getPameldt());
    }

    /**
     * Er påmeldt andre enn gitt arrangement?
     *
     * @param Int $arrangement_id
     * @return Bool
     */
    public function erPameldtAndre( Int $arrangement_id ) {
        return sizeof($this->getPameldtAndre( $arrangement_id )) > 0;
    }

    /**
     * Sett id
     *
     * @param integer $id
     * @return self
     **/
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Hent Id
     *
     * @return int $id
     **/
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sett fornavn
     *
     * @param String $fornavn
     * @return self
     **/
    public function setFornavn(String $fornavn)
    {
        $this->fornavn = Sanitizer::sanitizeNavn(stripslashes(mb_convert_case($fornavn, MB_CASE_TITLE, "UTF-8")));
        return $this;
    }
    /**
     * Hent fornavn
     *
     * @return String $fornavn
     **/
    public function getFornavn()
    {
        return $this->fornavn;
    }

    /**
     * Sett etternavn
     *
     * @param String $etternavn
     * @return self
     **/
    public function setEtternavn(String $etternavn)
    {
        $this->etternavn = Sanitizer::sanitizeEtternavn(stripslashes(mb_convert_case($etternavn, MB_CASE_TITLE, "UTF-8")));
        return $this;
    }

    /**
     * Hent etternavn
     *
     * @return String $etternavn
     **/
    public function getEtternavn()
    {
        return $this->etternavn;
    }

    /**
     * Hent fullt navn
     *
     * @return String CONCAT(getFornavn() + ' ' + getEtternavn())
     **/
    public function getNavn()
    {
        return $this->getFornavn() . ' ' . $this->getEtternavn();
    }

    /**
     * Sett mobil
     *
     * @param String $mobil
     * @return self
     **/
    public function setMobil(String $mobil)
    {
        $this->mobil = preg_replace("/[^0-9]/", "", $mobil);
        return $this;
    }

    /**
     * Hent mobil
     *
     * @return Int $mobil
     **/
    public function getMobil()
    {
        return (Int) $this->mobil;
    }

    /**
     * Sett e-post
     *
     * @param String $epost
     * @return self
     **/
    public function setEpost(String $epost)
    {
        $this->epost = $epost;
        return $this;
    }

    /**
     * Hent e-post
     *
     * @return String $epost
     **/
    public function getEpost()
    {
        return $this->epost;
    }

    /**
     * Sett rolle / instrument
     * 
     * (i.e. instrument for scene, film/flerkamera/tekst/foto for UKM Media osv)
     *
     * OBS OBS: setRolle MÅ kalles med key/value-array der key er funksjons-key og value er nicename. 
     *
     * @param String|Array $rolle
     * @return self
     */
    public function setRolle($rolle)
    {
        if (is_array($rolle)) {
            $rolle_object = array();
            $rolle_nicename = '';

            foreach ($rolle as $key => $r) {
                $rolle_object[] = $key;
                $rolle_nicename = $rolle_nicename . $r . ', ';
            }

            $this->setRolleObject($rolle_object);
            $rolle = rtrim($rolle_nicename, ', ');
        }

        $this->rolle = stripslashes($rolle);
        return $this;
    }

    /**
     * Hent rolle (i.e. instrument for scene, film/flerkamera/tekst/foto for UKM Media osv)
     *
     * @return String $rolle
     */
    public function getRolle()
    {
        return $this->rolle;
    }

    /**
     * Hent instrument
     * @see getRolle
     *
     * @return String $rolle
     **/
    public function getInstrument()
    {
        return $this->getRolle();
    }

    /**
     * JSON-encodes på vei inn i databasen, vanlig array ellers.
     * @param Array $rolleArray
     * @return self
     */
    public function setRolleObject($rolleArray)
    {
        $this->rolleObject = $rolleArray;
        return $this;
    }

    /**
     * Hent rolleObjekt
     *
     * @return Array $rolleObjekt
     */
    public function getRolleObject()
    {
        return $this->rolleObject;
    }


    /**
     * Sett fødselsdato
     *
     * @param integer unixtime $fodselsdato
     * @return self
     **/
    public function setFodselsdato($fodselsdato)
    {
        if (is_object($fodselsdato) && get_class($fodselsdato) == 'DateTime') {
            $fodselsdato = $fodselsdato->getTimestamp();
        }
        if (is_null($fodselsdato)) {
            $fodselsdato = 0;
        }
        $this->fodselsdato = $fodselsdato;
        return $this;
    }

    /**
     * Hent fødselsdato
     *
     * @return integer unixtime $fodselsdato
     **/
    public function getFodselsdato()
    {
        return $this->fodselsdato;
    }

    /**
     * Hent alder
     *
     * @param String $suffix
     * @return String alder
     **/
    public function getAlder($suffix = ' år')
    {
        if (0 == $this->getFodselsdato()) {
            return '25+' . $suffix;
        }
        $birthdate = new DateTime();
        $birthdate->setTimestamp($this->getFodselsdato());
        $now = new DateTime('now');

        return $birthdate->diff($now)->y . $suffix;
    }

    /**
     * Hent alder i år (kun tall)
     *
     * @return Int $alder
     */
    public function getAlderTall()
    {
        return $this->getAlder(null);
    }

    /**
     * Sett hjem-kommune (og fylke)
     *
     * @param Int $kommune_id
     * @return self
     **/
    public function setKommune(Int $kommune_id)
    {
        if (Kommune::validateClass($kommune_id)) {
            $this->kommune_id = $kommune_id->getId();
        } else {
            $this->kommune_id = $kommune_id;
        }
        $this->kommune = null;
        return $this;
    }

    /**
     * Hent hjem-kommune
     *
     * @return Kommune
     **/
    public function getKommune()
    {
        if (null == $this->kommune) {
            $this->kommune = new Kommune($this->kommune_id);
        }
        return $this->kommune;
    }

    /**
     * Hent hjem-fylke
     *
     * @return Fylke
     **/
    public function getFylke()
    {
        if (null == $this->fylke) {
            $this->fylke = $this->getKommune()->getFylke();
        }
        return $this->fylke;
    }

    /**
     * Gjett hvilket kjønn er
     *
     * OBS: krever databasespørring!
     *
     * @return String male|female|unknown
     **/
    public function getKjonn()
    {
        $first_name = explode(" ", str_replace("-", " ", $this->getFornavn()));
        $first_name = $first_name[0];

        $qry = "SELECT `kjonn`
				FROM `ukm_navn`
				WHERE `navn` = '" . $first_name . "' ";

        $qry = new Query($qry);
        $res = $qry->run('field', 'kjonn');

        return ($res == null) ? 'unknown' : $res;
    }

    /**
     * Hent kjønnspronomen 
     * 
     * Baserer seg på gjetning fra getKjonn
     * @see getKjonn()
     *
     * @return String (han|hun|han/hun)
     */
    public function getKjonnspronomen()
    {
        #echo $this->getNavn() .': '. $this->getKjonn();
        switch ($this->getKjonn()) {
            case 'male':
                return 'han';
            case 'female':
                return 'hun';
            default:
                return 'han/hun';
        }
    }

    /**
     * Hent samling for sensitive data
     * OBS: HEAVY LOGGING
     *
     * @return PersonSensitivt
     */
    public function getSensitivt()
    {
        if (null == $this->sensitivt) {
            $this->sensitivt = new PersonSensitivt($this->getId());
        }
        return $this->sensitivt;
    }

    /**
     * Hent adresse
     * Sjeldent vi har dette
     * 
     * @return String $adresse
     */
    public function getAdresse()
    {
        return $this->adresse;
    }

    /**
     * Sett adresse
     * 
     * @param String $adresse
     * @return self
     */
    public function setAdresse(String $adresse)
    {
        $this->adresse = $adresse;

        return $this;
    }

    /**
     * Hent postnummer
     */
    public function getPostnummer()
    {
        return $this->postnummer;
    }

    /**
     * Sett nytt postnummer
     *
     * @param String $postnummer
     * @return self
     */
    public function setPostnummer(String $postnummer)
    {
        $this->postnummer = $postnummer;
        return $this;
    }

    /**
     * Hent poststed
     */
    public function getPoststed()
    {
        return $this->poststed;
    }

    /**
     * Sett nytt poststed
     *
     * @return self
     */
    public function setPoststed(String $poststed)
    {
        $this->poststed = $poststed;

        return $this;
    }

    /**
     * Sett context
     *
     * @param Context $context
     * @return void
     */
    public function setContext(Context $context)
    {
        $this->context = $context;
        return $this;
    }
    /**
     * Hent context
     *
     * @return Context
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Opprett ny person-instance
     *
     * @param 
     */
    public function __construct($person)
    {
        $this->attributes = [];
        if (is_numeric($person)) {
            $this->_load_from_db($person);
        } elseif (is_array($person)) {
            $this->_load_from_array($person);
        } else {
            throw new Exception(
                'PERSON: Oppretting krever parameter $person som numerisk id eller array, fikk ' . gettype($person) . '.',
                109001
            );
        }
    }

    /**
     * Last inn info fra databasen
     *
     * @param Int $id
     * @return Person
     */
    private function _load_from_db($id)
    {
        $sql = new Query(
            Person::getLoadQuery() . "
            WHERE `p_id` = '#p_id'",
            [
                'p_id' => $id
            ]
        );
        $res = $sql->getArray();
        return $this->_load_from_array($res);
    }
    /**
     * Last inn info fra et array
     *
     * @param Array $data
     * @return void
     */
    private function _load_from_array(array $row)
    {
        $this->setId($row['p_id']);
        $this->setFornavn($row['p_firstname']);
        $this->setEtternavn($row['p_lastname']);
        $this->setMobil($row['p_phone']);
        $this->setEpost($row['p_email']);
        $this->setFodselsdato($row['p_dob']);
        $this->setKommune($row['p_kommune']);
        if (array_key_exists('instrument', $row)) {
            $this->setRolle($row['instrument']);
        }
        if (array_key_exists('instrument_object', $row)) {
            $roller = json_decode($row['instrument_object']);
            // Prøv å hente ut roller som id => tekst
            // da dette gir bedre verdi for getRolle() / getInstrument()
            if( is_array( $roller ) ) {
                try {
                    if( is_numeric($row['bt_id']) ) {
                        $innslag_type = Typer::getById( $row['bt_id'] );
                        $roller = $innslag_type->getValgteFunksjonerSomKeyVal( $roller );
                    }
                } catch( Exception $e ) {
                    // Ignorer feil - da turer vi bare på med opprinnelig verdi
                }
                $this->setRolle($roller);
            } else {
                $this->setRolleObject($roller);
            }
        }

        $pameldt_til = [];
        // Gammel standard for videresending
        if (array_key_exists('pl_ids', $row)) {
            $pameldt_til = explode(',', $row['pl_ids']);
        }
        // Ny standard (2020) for påmelding og videresending
        if (array_key_exists('arrangementer', $row)) {
            $pameldt_til = array_merge(explode(',', $row['arrangementer']));
        }
        $this->pameldt_til = array_unique($pameldt_til);
    }

    /**
     * Sjekk om gitt objekt er gyldig person-objekt
     *
     * @param Any $object
     * @return Bool
     */
    public static function validateClass($object)
    {
        return is_object($object) &&
            in_array(
                get_class($object),
                [
                    'UKMNorge\Innslag\Personer\Person',
                    'UKMNorge\Innslag\Personer\Kontaktperson'
                ]
            );
    }
}
