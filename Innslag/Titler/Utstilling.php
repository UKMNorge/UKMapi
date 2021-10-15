<?php

namespace UKMNorge\Innslag\Titler;

use Exception;
use UKMNorge\Innslag\Media\Bilder\Bilde;
use UKMNorge\Innslag\Playback\Playback;

class Utstilling extends Tittel
{
    public const TABLE = 'smartukm_titles_exhibition';
    public const TABLE_NAME_COL = 't_e_title';
    public $type;
    public $teknikk = null;
    public $beskrivelse = null;
    public $bilde = null; // Bilde
    public $playback = null; // Kunstverk som er lastet opp på playbackserver og venter for godkjenning

    public $bildeId;
    public $playbackId;

    /**
     * Returner data som typisk står i parentes
     *
     * @return String
     */

    public function getParentes()
    {
        $tekst = '';
        if (!empty($this->getType())) {
            $tekst .= 'Type: ' . $this->getType() . ' ';
        }
        if (!empty($this->getTeknikk())) {
            $tekst .= 'Teknikk: ' . $this->getTeknikk() . ' ';
        }

        return rtrim($tekst);
    }

    /**
     * Sett objekt-data fra databaserad
     * 
     * Kalles fra Tittel
     *
     * @param Array $row
     * @return Bool true
     */
    public function populate(array $row)
    {
        $this->setTittel(stripslashes($row['t_e_title']));
        $this->setType($row['t_e_type']);
        $this->setTeknikk($row['t_e_technique']);
        $this->setBildeId($row['t_e_bilde_kunstverk']);
        $this->setPlaybackId($row['pb_id']);
        #$this->setFormat($row['t_e_format']);
        $this->setBeskrivelse($row['t_e_comments']);
        $this->setVarighet(0);
    }

    /**
     * Sett type (for bl.a. utstilling)
     *
     * @param string $type
     *
     * @return $this;
     **/
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    /**
     * Hent type
     *
     * @return innslag_type $type
     **/
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sett beskrivelse (av kunstverk)
     *
     * @param beskrivelse
     * @return $this
     **/
    public function setBeskrivelse($beskrivelse)
    {
        $this->beskrivelse = $beskrivelse;
        return $this;
    }
    /**
     * Hent beskrivelse
     *
     * @return string $beskrivelse
     **/
    public function getBeskrivelse()
    {
        return $this->beskrivelse;
    }

    /**
     * Sett teknikk (av kunstverk)
     *
     * @param teknikk
     * @return $this
     **/
    public function setTeknikk($teknikk)
    {
        $this->teknikk = $teknikk;
        return $this;
    }
    /**
     * Hent teknikk
     *
     * @return string $teknikk
     **/
    public function getTeknikk()
    {
        return $this->teknikk;
    }

    /**
     * Hent Playback for bilde kunstverk
     *
     * @return Playback $playback
     **/
    public function getPlayback()
    {   
        if(!$this->playbackId) {
            return null;
        }

        try {
            $this->playback = Playback::getById((int)$this->playbackId);
        }
        catch (Exception $e) {
            return null;
        }
        
        return $this->playback;
    }

    /**
     * Hent bilde kunstverk
     *
     * @return Bilde $bilde
     **/
    public function getBilde()
    {
        if(!$this->bildeId) {
            return null;
        }

        try {
            $this->bilde = Bilde::getById((int)$this->bildeId);
        }
        catch (Exception $e) {
            return null;
        }
        
        return $this->bilde;
    }


    /**
     * Hent Bilde id
     * Brukes for database
     *
     **/
    public function getBildeId()
    {
        if($this->bildeId) {
            return $this->bildeId;
        }
        return "NULL";
    }

    /**
     * set bilde id
     *
     * @return int
     **/
    public function setBildeId($bildeId) {
        $this->bildeId = $bildeId;
    }

    /**
     * Hent Playback id
     * Brukes for database
     *
     **/
    public function getPlaybackId()
    {
        if($this->playbackId) {
            return $this->playbackId;
        }
        return 'NULL';
    }

    /**
     * set bilde id
     *
     * @return int
     **/
    public function setPlaybackId($playbackId) {
        $this->playbackId = $playbackId;
    }
}
