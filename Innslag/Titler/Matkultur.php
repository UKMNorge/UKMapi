<?php

namespace UKMNorge\Innslag\Titler;

class Matkultur extends Tittel
{
    public const TABLE = 'smartukm_titles_other';
    public const TABLE_NAME_COL = 't_o_function';


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
    public function populate(array $row) {
        $this->setTittel(stripslashes($row['t_o_function']));
        $this->setVarighet(0);
    }
}
