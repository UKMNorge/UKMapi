<?php

namespace UKMNorge\Geografi;

use UKMNorge\Collection;

class Kommuner extends Collection
{

    /**
     * Hent ID-array for alle kommuner i collection
     *
     * @return Array<Int>
     */
    public function getIdArray()
    {
        $array = array();
        foreach ($this as $kommune) {
            $array[] = $kommune->getId();
        }
        return $array;
    }

    /**
     * Hent alle kommuneID + navn
     *
     * Key = kommune_id
     * Val = kommune_navn
     * 
     * @return Array<String>
     */
    public function getKeyValArray()
    {
        $array = array();
        foreach ($this as $kommune) {
            $array[$kommune->getId()] = $kommune->getNavn();
        }
        return $array;
    }

    /**
     * toString == getNavn(), getNavn(), ...
     *
     * @return string
     */
    public function __toString()
    {
        $string = '';
        foreach ($this as $kommune) {
            $string .= $kommune->getNavn() . ', ';
        }
        return rtrim($string, ', ');
    }

    /*
     * Hent alle fylker (til kommunene) i samlingen
     * 
     * @return Array<Fylke> $fylker
    **/
    public function getFylker()
    {
        $added = [];
        $fylker = [];
        foreach ($this->getAll() as $kommune) {
            $fylke = $kommune->getFylke();
            if (!in_array($fylke->getId(), $added)) {
                $fylker[] = $fylke;
                $added[] = $fylke->getId();
            }
        }
        return $fylker;
    }
}
