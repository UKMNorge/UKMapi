<?php

namespace UKMNorge\Arrangement\Skjema;

use Exception;

class Svar {
    private $id;
    private $sporsmal_id;
    private $respondent_type;
    private $respondent_id;
    private $value;
    private $value_raw;
    private $value_updated = false;

    /**
     * Opprett objekt fra databasen
     *
     * @param Array $db_row
     * @return Svar $svar
     */
    public static function getFromDatabaseRow( Array $db_row ) {
        if( !empty($db_row['pl_fra']) ) {
            $respondent_type = 'arrangement';
            $respondent_id = intval($db_row['pl_fra']);
        } else {
            $respondent_type = 'person';
            $respondent_id = intval($db_row['p_fra']);
        }

        return new Svar(
            intval($db_row['id']),
            intval($db_row['sporsmal']),
            $respondent_type,
            $respondent_id,
            $db_row['svar']
        );
    }

    /**
     * Hent svar for en respondent
     * 
     * @param Int $sporsmal_id
     * @param String $respondent_type <arrangement|person>
     * @param Int $respondent_id
     * @return Svar
     */
    public static function getPlaceholder( Int $sporsmal_id, String $respondent_type, Int $respondent_id) {
        return new static(
            0,
            $sporsmal_id,
            $respondent_type,
            $respondent_id,
            ''
        );
    }

    /**
     * Opprett et nytt objekt
     *
     * @param Int $id
     * @param Int $sporsmal_id
     * @param Int $pl_fra
     * @param String $json_string
     */
    public function __construct( Int $id, Int $sporsmal_id, String $respondent_type, Int $respondent_id, String $json_string ) {
        $this->id = $id;
        $this->sporsmal_id = $sporsmal_id;
        $this->respondent_id = $respondent_id;
        $this->respondent_type = $respondent_type;
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
    public function getValue( $value_key = null)
    {
        if( is_null($this->value) ) {
            $this->value = json_decode( $this->getValueRaw() );
        }
        // Hvis vi leter etter en "underverdi" av verdien
        if( !is_null($value_key)) {
            if( isset( $this->value->$value_key ) ) {
                return $this->value->$value_key;
            }
            return $this->value;
        }
        return $this->value;
    }

    /**
     * Sett ny verdi for svaret
     *
     * @param Array|String $value
     * @return self
     */
    public function setValue($value) {
        if( json_encode($value) != $this->getValueRaw() ) {
            // Cast array to object fordi json henter array som objekt
            // (og det er allerede i bruk flere steder)
            if( is_array($value)) {
                $value = (object) $value;
            }
            $this->value_raw = json_encode($value);
            $this->value = $value;
            $this->value_updated = true;
        }
       return $this;
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

    /**
     * Har verdien endret seg (og skal lagres?)
     *
     * @return Bool
     */
    public function isChanged() {
        return $this->value_updated;
    }

    /**
     * Sett ny ID for svaret
     * 
     * Brukes av Write::_saveSporsmal() etter suksessfull lagring
     *
     * @param Int $id
     * @return self
     */
    public function setId( Int $id ) {
        $this->id = $id;
        return $this;
    }
}