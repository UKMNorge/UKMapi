<?php

namespace UKMNorge\Innslag\Titler;

use Exception;
use UKMNorge\Tid;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Write as WriteArrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Typer\Type;

require_once('UKM/Autoloader.php');

abstract class Tittel
{
    /**
     * Sett objekt-data fra databaserad
     * 
     * Kalles fra Tittel
     *
     * @param Array $row
     * @return Bool true
     */
    abstract function populate(array $row);

    var $context = null;
    var $table = null;
    var $id = null;
    var $videresendtTil = null;
    var $tittel = null;
    var $varighet = null;
    var $sekunder = null;
    var $selvlaget = null;
    var $pameldt_til = [];

    /**
     * Opprett tittel-objektet
     *
     * @param Array $row
     */
    public function __construct(array $row)
    {
        $this->id = $row['t_id'];

        $pameldt_til = [];
        // Gammel standard for videresending
        if (array_key_exists('pl_ids', $row)) {
            $pameldt_til = explode(',', $row['pl_ids']);
        }
        // Ny standard (2020) for påmelding og videresending
        if( array_key_exists('arrangementer', $row)) {
            $pameldt_til = array_merge( $pameldt_til, explode(',', $row['arrangementer'] ) );
        }
        $this->pameldt_til = array_unique( $pameldt_til );
        $this->populate($row);
    }

    /**
     * Hent en tittel fra ID
     *
     * @param Int $id
     * @return Tittel
     */
    public static function getById(Int $id)
    {
        $qry = new Query(
            "SELECT * 
            FROM `" . get_called_class()::TABLE . "`
            WHERE `t_id` = '#id'",
            [
                'id' => $id
            ]
        );

        $row = $qry->getArray();
        if (!$row) {
            #echo $qry->debug();
            throw new Exception(
                'Kunne ikke hente inn tittel ' . $id,
                109001
            );
        }

        $classname = get_called_class();
        $tittel = new $classname($row);
        return $tittel;
    }

    /**
     * Sett tittel
     *
     * @param string $tittel
     * @return $this
     **/
    public function setTittel($tittel)
    {
        $this->tittel = $tittel;
        return $this;
    }

    /**
     * Hent tittel
     *
     * @return string $tittel
     *
     **/
    public function getTittel()
    {
        return $this->tittel;
    }

    /**
     * @alias getTittel()
     */
    public function getNavn()
    {
        return $this->getTittel();
    }

    /**
     * Sett varighet
     *
     * @param int $sekunder
     * @return $this
     **/
    public function setVarighet($sekunder)
    {
        $this->sekunder = $sekunder;
        $this->varighet = new Tid($sekunder);
        return $this;
    }

    /**
     * Hent varighet
     *
     * @return object tid
     *
     **/
    public function getVarighet()
    {
        return $this->varighet;
    }

    /**
     * Hent varigheten, men som sekunder
     *
     * @return Int varighet i sekunder
     **/
    public function getVarighetSomSekunder()
    {
        return $this->sekunder;
    }

    /**
     * Hent varigheten av tittelen som sekunder
     * 
     * @alias getVarighetSomSekunder()
     * @return Int $sekunder
     */
    public function getSekunder()
    {
        return $this->getVarighetSomSekunder();
    }

    /**
     * Sett selvalget
     *
     * @param bool selvlaget
     * @return $this
     **/
    public function setSelvlaget($selvlaget)
    {
        if (!is_bool($selvlaget)) {
            throw new Exception('Tittel: Selvlaget må angis som boolean');
        }
        $this->selvlaget = $selvlaget;
        return $this;
    }

    /**
     * Hent selvlaget
     *
     * @return bool selvlaget
     **/
    public function erSelvlaget()
    {
        return $this->selvlaget;
    }
    public function getSelvlaget()
    {
        return $this->erSelvlaget();
    }

    /**
     * Sett ID
     *
     * @param integer id 
     *
     * @return $this
     **/
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Hent ID
     * @return integer $id
     **/
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sett hvilke arrangemtn-IDer tittelen er påmeldt
     *
     * @param Array<Int> ID
     * @return $this
     **/
    public function setPameldt(Array $pameldt_til)
    {
        $this->pameldt_til = $pameldt_til;
        return $this;
    }

    /**
     * Legg til enda et arrangement hvor tittelen er påmeldt
     *
     * @param Int $pameldt_til
     * @return Bool true
     */
    public function addPameldt(Int $pameldt_til ) {
        if( !in_array( $pameldt_til, $this->pameldt_til ) ) {
            $this->pameldt_til[] = $pameldt_til;
        }
        return true;
    }
    
    /**
     * Hent hvilke arrangement-IDer tittelen er påmeldt
     * 
     * Gjelder også på lokalmønstring fra og med 2020
     * 
     * @return Array<Int> $videresendtTil
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
     * Sett tittelens context
     *
     * @param [type] $context
     * @return void
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Hent tittelens context
     *
     * @return void
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Sjekk at gitt objekt er gyldig tittel-klasse
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
                    'UKMNorge\Innslag\Titler\Tittel',
                    'UKMNorge\Innslag\Titler\Annet',
                    'UKMNorge\Innslag\Titler\Dans',
                    'UKMNorge\Innslag\Titler\Film',
                    'UKMNorge\Innslag\Titler\Litteratur',
                    'UKMNorge\Innslag\Titler\Matkultur',
                    'UKMNorge\Innslag\Titler\Musikk',
                    'UKMNorge\Innslag\Titler\Teater',
                    'UKMNorge\Innslag\Titler\Utstilling'
                ]
            );
    }

    /**
     * Hva heter tittel-klassen?
     * 
     * @param String $innslag_type
     * @return String Tittel-klasse
     */
    public static function getTittelClassFromInnslagType( String $type ) {
        switch( $type ) {
            case 'annet':
            case 'dans':
            case 'film':
            case 'litteratur':
            case 'matkultur':
            case 'musikk':
            case 'teater':
            case 'utstilling':
                return ucfirst($type);
            case 'video':
                return 'Film';
            case 'scene':
                return 'Annet';
            default:
                throw new Exception(
                    'Kan ikke konvertere innslag-type "'. $type .'" til tittel-klasse. '.
                    'Kontakt support@ukm.no'
                );
        }
    }
}
