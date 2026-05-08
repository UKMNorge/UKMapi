<?php

namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Database\SQL\Query;

use Exception;

class Sporsmal {
    private $id;
    private $skjema;
    private $rekkefolge;

    private $type;
    private $tittel;
    private $tekst;
    private $is_required;

    /**
     * Opprett spørsmål fra databaserad
     *
     * @param Array $db_row
     * @return Sporsmal $sporsmal
     */
    public static function createFromDatabase( $db_row ) {
        return new Sporsmal(
            $db_row['id'],
            $db_row['skjema'],
            $db_row['rekkefolge'],
            $db_row['type'],
            $db_row['tittel'],
            $db_row['tekst'],
            isset($db_row['is_required']) ? (bool) $db_row['is_required'] : true
        );
    }

    public function __construct( Int $id, Int $skjema_id, Int $rekkefolge, String $type, String $tittel, String $tekst, bool $is_required = true )
    {
        $this->id = $id;
        $this->skjema = $skjema_id;
        $this->rekkefolge = $rekkefolge;
        $this->type = $type;
        $this->tittel = $tittel;
        $this->tekst = $tekst;
        $this->is_required = $is_required;
    }

    public static function getById(Int $id) : Sporsmal {
        $sql = new Query(
            "SELECT *
            FROM `ukm_videresending_skjema_sporsmal`
            WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );
        $data = $sql->getArray();
        if ($data) {
            return new Sporsmal(
                $data['id'],
                $data['skjema'],
                $data['rekkefolge'],
                $data['type'],
                $data['tittel'],
                $data['tekst'],
                isset($data['is_required']) ? (bool) $data['is_required'] : true
            );
        }
        throw new Exception('Could not find spørsmål with id: '. $id);
    }

    /**
     * Hent spørsmålets ID
     * Brukes kun for database-interaksjon
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hvilket skjema er dette spørsmålet for?
     */ 
    public function getSkjemaId()
    {
        return $this->skjema;
    }

    /**
     * Hvilket nummer har dette spørsmålet i skjemaet
     * 
     * @return Int $rekkefolge
     */ 
    public function getRekkefolge()
    {
        return $this->rekkefolge;
    }

    /**
     * Hent type spørsmål
     * 
     * @return String $type
     */ 
    public function getType()
    {
        return $this->type;
    }

    /**
     * Hent spørsmålets tittel (spørsmålet, altså 🤯)
     * 
     * @return String $tittel;
     */ 
    public function getTittel()
    {
        return $this->tittel;
    }

    /**
     * Hent spørsmålets hjelpetekst
     * 
     * @return String $hjelpetekst
     */ 
    public function getTekst()
    {
        return $this->tekst;
    }

    /**
     * Er spørsmålet obligatorisk å svare på?
     */
    public function isRequired(): bool
    {
        return (bool) $this->is_required;
    }

    /**
     * @return self
     */
    public function setIsRequired($is_required)
    {
        $this->is_required = (bool) $is_required;

        return $this;
    }

    /**
     * Set the value of tekst
     *
     * @return  self
     */ 
    public function setTekst($tekst)
    {
        $this->tekst = $tekst;

        return $this;
    }

    /**
     * Set the value of tittel
     *
     * @return  self
     */ 
    public function setTittel($tittel)
    {
        $this->tittel = $tittel;

        return $this;
    }

    /**
     * Set the value of type
     *
     * @return  self
     */ 
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return self
     */
    public function setRekkefolge($rekkefolge)
    {
        $this->rekkefolge = (int) $rekkefolge;

        return $this;
    }
}