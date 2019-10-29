<?php

namespace UKMNorge\Innslag\Titler;

class Teater extends Tittel
{
    public const TABLE = 'smartukm_titles_scene';
    public const TABLE_NAME_COL = 't_name';
    public $tekst_av;

    /**
     * Returner data som typisk står i parentes
     *
     * @return String
     */
    public function getParentes()
    {
        return ''; // Tidligere $this->getFormat()
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
        $this->setVarighet((int) $row['t_time']);
        $this->setSelvlaget(1 == (int) $row['t_selfmade']);
    }

    /**
     * Sett tekst av
     *
     * @param $tekst_av
     * @return $this
     **/
    public function setTekstAv(String $tekst_av)
    {
        $this->tekst_av = $tekst_av;
        return $this;
    }
    /**
     * Hent tekst av
     *
     * @return String $tekst_av
     *
     **/
    public function getTekstAv()
    {
        return $this->tekst_av;
    }
}
