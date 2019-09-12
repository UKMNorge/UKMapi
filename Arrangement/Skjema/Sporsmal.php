<?php

namespace UKMNorge\Arrangement\Skjema;

class Sporsmal {
    private $id;
    private $skjema;
    private $rekkefolge;

    private $type;
    private $tittel;
    private $tekst;

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
            $db_row['tekst']
        );
    }

    public function __construct( Int $id, Int $skjema_id, Int $rekkefolge, String $type, String $tittel, String $tekst )
    {
        $this->id = $id;
        $this->skjema = $skjema_id;
        $this->rekkefolge = $rekkefolge;
        $this->type = $type;
        $this->tittel = $tittel;
        $this->tekst = $tekst;
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
}