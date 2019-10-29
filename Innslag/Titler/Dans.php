<?php

namespace UKMNorge\Innslag\Titler;

class Dans extends Tittel
{
    public const TABLE = 'smartukm_titles_scene';
    public const TABLE_NAME_COL = 't_name';

    public $koreografi_av;

    /**
     * Returner data som typisk står i parentes
     *
     * @return String
     */
    public function getParentes()
    {
        return 'Koreografi: ' . $this->getKoreografiAv();
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
        $this->setKoreografiAv($row['t_coreography']);
        $this->setVarighet((int) $row['t_time']);
        $this->setSelvlaget(1 == (int) $row['t_selfmade']);
    }

    /**
     * Sett koreografi av
     *
     * @param string $koreografi_av
     * @return $this
     **/
    public function setKoreografiAv($koreografi_av)
    {
        $this->koreografi_av = $koreografi_av;
        return $this;
    }

    /**
     * @alias setKoreografiAv()
     */
    public function setKoreografi(String $koreografi_av)
    {
        return $this->setKoreografiAv($koreografi_av);
    }
    
    /**
     * Hent koreografi av
     *
     * @return string $koreografi_av
     *
     **/
    public function getKoreografiAv()
    {
        return $this->koreografi_av;
    }
}
