<?php

namespace UKMNorge\Innslag\Titler;

class Musikk extends Tittel
{
    public const TABLE = 'smartukm_titles_scene';
    public const TABLE_NAME_COL = 't_name';
    public $koreografi_av;
    public $tekst_av;
    public $melodi_av;
    public $instrumental;

    /**
     * Returner data som typisk står i parentes
     *
     * @return String
     */
    public function getParentes()
    {
        if ($this->getTekstAv() == $this->getMelodiAv()) {
            return 'Tekst og melodi: ' . $this->getTekstAv();
        }

        $tekst = '';
        if (!empty($this->getTekstAv())) {
            $tekst .= 'Tekst: ' . $this->getTekstAv() . ' ';
        }
        if (!empty($this->getMelodiAv())) {
            $tekst .= 'Melodi: ' . $this->getMelodiAv() . ' ';
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
        $this->setTittel(stripslashes($row['t_name']));
        $this->setTekstAv($row['t_titleby']);
        $this->setMelodiAv($row['t_musicby']);

        $this->setVarighet((int) $row['t_time']);

        $this->setSelvlaget(1 == (int) $row['t_selfmade']);
        $this->setInstrumental(1 == (int) $row['t_instrumental']);

        if ($this->erInstrumental()) {
            $this->setTekstAv('');
        }
    }

    /**
     * Sett tekst av
     *
     * @param $tekst_av
     * @return $this
     **/
    public function setTekstAv($tekst_av)
    {
        $this->tekst_av = $tekst_av;
        return $this;
    }
    /**
     * Hent tekst av
     *
     * @return string $tekst_av
     *
     **/
    public function getTekstAv()
    {
        return $this->tekst_av;
    }

    /** 
     * Sett melodi av
     * 
     * @param string $melodi_av
     * @return $melodi_av
     **/
    public function setMelodiAv($melodi_av)
    {
        $this->melodi_av = $melodi_av;
        return $this;
    }
    /**
     * Hent melodi av
     *
     * @return string $melodi_av
     **/
    public function getMelodiAv()
    {
        return $this->melodi_av;
    }

    /**
     * Sett instrumental
     *
     * @param bool instrumental
     * @return $this
     **/
    public function setInstrumental($instrumental)
    {
        if (!is_bool($instrumental)) {
            throw new Exception('Tittel: Instrumental må angis som boolean');
        }
        $this->instrumental = $instrumental;
        return $this;
    }

    /**
     * Er instrumental?
     *
     * @return bool $instrumental
     **/
    public function erInstrumental()
    {
        return $this->instrumental;
    }
    public function getInstrumental()
    {
        return $this->erInstrumental();
    }
}
