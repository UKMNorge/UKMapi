<?php

namespace UKMNorge\Innslag\Titler;

class Film extends Tittel
{
    public const TABLE = 'smartukm_titles_video';
    public const TABLE_NAME_COL = 't_v_title';


    public $format;

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
        $this->setTittel(stripslashes($row['t_v_title']));
        $this->setVarighet((int) $row['t_v_time']);
        #$this->setFormat($row['t_v_format']);
        return true;
    }

    /**
     * Sett format
     *
     * @param format
     * @return $this
     **/
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }
    /**
     * Hent format
     *
     * @return string $format
     **/
    public function getFormat()
    {
        return $this->format;
    }
}
