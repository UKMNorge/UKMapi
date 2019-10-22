<?php

namespace UKMNorge\Arrangement\Skjema;

use Exception;

class Svar {
    private $id;
    private $sporsmal_id;
    private $fra;
    private $value;
    private $value_raw;

    /**
     * Opprett objekt fra databasen
     *
     * @param Array $db_row
     * @return Svar $svar
     */
    public static function createFromDatabase( $db_row ) {
        return new Svar(
            $db_row['id'],
            $db_row['sporsmal'],
            $db_row['pl_fra'],
            $db_row['svar']
        );
    }

    public static function createFromId( $id ) {
        throw new Exception(
            'Ikke mulig: createFromId bør være unødvendig å kjøre, da du bør behandle svaret gjennom riktig SvarSett.',
            154001
        );
    }

    /**
     * Opprett tom placeholder
     *
     * @return Svar $empty
     */
    public static function createEmpty() {
        return new Svar(0,0,0,'');
    }

    /**
     * Opprett et nytt objekt
     *
     * @param Int $id
     * @param Int $sporsmal_id
     * @param Int $pl_fra
     * @param String $json_string
     */
    public function __construct( Int $id, Int $sporsmal_id, Int $pl_fra, String $json_string ) {
        $this->id = $id;
        $this->sporsmal_id = $sporsmal_id;
        $this->fra = $pl_fra;
        $this->value_raw = $json_string;
    }

    /**
     * Hent svarets ID. 
     * Brukes kun for database-interaksjon
     * 
     * @return Int $id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hvilket spørsmål er dette svar for?
     * 
     * @return Int $sporsmal_id
     */ 
    public function getSporsmalId()
    {
        return $this->sporsmal_id;
    }

    /**
     * Hent svarets eier
     * Hvem har svart?
     * 
     * @return Int $pl_fra
     */ 
    public function getFra()
    {
        return $this->fra;
    }

    /**
     * Hent den faktiske verdien
     * 
     * @return Any $svar
     */ 
    public function getValue()
    {
        if( null == $this->value ) {
            $this->value = json_decode( $this->getValueRaw() );
        }
        return $this->value;
    }

    /**
     * Hent rå-verdi for svar som json-string
     * 
     * @return String $json_data;
     */ 
    public function getValueRaw()
    {
        return $this->value_raw;
    }
}