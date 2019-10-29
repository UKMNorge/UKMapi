<?php

namespace UKMNorge\Innslag\Titler;

class Annet extends Tittel
{
    public const TABLE = 'smartukm_titles_scene';
    public const TABLE_NAME_COL = 't_name';

    public $erfaring;
    public $kommentar;

    /**
     * Returner data som typisk står i parentes
     *
     * @return String
     */
    public function getParentes()
    {
        return '';
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
        $this->setVarighet($row['t_time']);
        return true;
    }

    /**
     * Sett erfaring
     *
     * @param string $erfaring
     * @return $this
     **/
    public function setErfaring($erfaring)
    {
        $this->erfaring = $erfaring;
        return $this;
    }
    /**
     * Hent erfaring
     *
     * @return string $erfaring
     *
     **/
    public function getErfaring()
    {
        return $this->erfaring;
    }

    /**
     * Sett kommentar
     *
     * @param string $kommentar
     * @return $this
     **/
    public function setKommentar($kommentar)
    {
        $this->kommentar = $kommentar;
        return $this;
    }
    /**
     * Hent kommentar
     *
     * @return string $kommentar
     *
     **/
    public function getKommentar()
    {
        return $this->kommentar;
    }
}
