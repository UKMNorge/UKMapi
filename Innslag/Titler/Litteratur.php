<?php

namespace UKMNorge\Innslag\Titler;

class Litteratur extends Tittel
{
    public const TABLE = 'smartukm_titles_scene';
    public const TABLE_NAME_COL = 't_name';
    public $litteratur_read;
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
        $this->setLesOpp(1 == (int) $row['t_litterature_read']);
        $this->setVarighet((int) $row['t_time']);
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
     * Sett skal litteratur leses opp?
     *
     * @param bool litteratur_read
     * @return $this
     **/
    public function setLesOpp(Bool $lesopp)
    {
        $this->litteratur_read = $lesopp;
        return $this;
    }
    /**
     * Skal litteratur leses opp?
     *
     * @return bool selvlaget
     **/
    public function erLesOpp()
    {
        return $this->litteratur_read;
    }
    /**
     * ALIAS Skal litteratur leses opp?
     *
     * @return bool selvlaget
     **/
    public function skalLesesOpp()
    {
        return $this->erLesOpp();
    }
    public function getLesOpp()
    {
        return $this->erLesOpp();
    }
}
