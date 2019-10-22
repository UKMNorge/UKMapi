<?php

namespace UKMNorge\Geografi;

use Collection;
require_once('UKM/_collection.class.php');

class Kommuner extends Collection
{

    public function getIdArray()
    {
        $array = array();
        foreach ($this as $kommune) {
            $array[] = $kommune->getId();
        }
        return $array;
    }

    public function getKeyValArray()
    {
        $array = array();
        foreach ($this as $kommune) {
            $array[$kommune->getId()] = $kommune->getNavn();
        }
        return $array;
    }

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
         * @return Array[ Fylke ] $fylker
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