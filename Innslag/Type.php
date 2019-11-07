<?php

namespace UKMNorge\Innslag;

use Exception;

require_once('UKM/Autoloader.php');

class Type
{
    var $id = null;
    var $key = null;
    var $name = null;
    var $icon = null;
    var $har_filmer = false; # Kan det finnes noe i UKM-TV?
    var $har_titler = false;
    var $tabell = false;
    var $har_tekniske_behov = false;
    var $har_sjanger = false;
    var $funksjoner = null;

    public function __construct($id, $key, $name, $har_filmer, $har_titler, $funksjoner, $tabell, $har_tekniske_behov, $har_sjanger, $tekst)
    {
        $this->id = $id;
        $this->key = strtolower($key);
        $this->name = $name;
        $this->har_sjanger = $har_sjanger;
        $this->har_titler = $har_titler;
        $this->har_tekniske_behov = $har_tekniske_behov;
        $this->har_filmer = $har_filmer;
        $this->tabell = $tabell;
        $this->funksjoner = $funksjoner;
        if( is_array($tekst) && sizeof($tekst) > 0 ) {
            $this->tekst = static::arrayToDotKey('', $tekst, []);
        }
    }

    public static function arrayToDotKey( $base, $array, $output ) {
        foreach( $array as $key => $value ) {
            if( is_array( $value ) ) {
                $output = array_merge( $output, static::arrayToDotKey( $base .'.'. $key, $value, $output ) );
            } else {
                $output[trim( $base .'.'. $key, '.')] = $value;
            }
        }
        return $output;
    }

    public function _($key, $sub=null) {
        return $this->getTekst($key, $sub);
    }
    public function getText($key, $sub=null) {
        return $this->getTekst($key, $sub);
    }
    public function getTekst($key, $sub=null) {
        if(!isset($this->tekst[$key])) {
            return $key;
        }
        if( is_array($sub)) {
            return str_replace( array_keys($sub), array_values($sub), $this->tekst[$key]);
        }
        return $this->tekst[$key];
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    public function getId()
    {
        return $this->id;
    }
    /**
     * Hent type-ID som string (key)
     *
     * @return String
     */
    public function getKey()
    {
        return $this->key;
    }

    public function getNavn()
    {
        return $this->name;
    }

    public function getFunksjoner()
    {
        return $this->funksjoner;
    }
    public function harFunksjoner()
    {
        return is_array($this->funksjoner);
    }

    public function getTabell()
    {
        return $this->tabell;
    }

    public function harTid()
    {
        return $this->getTabell() != false;
    }

    public function harFilmer()
    {
        return $this->har_filmer;
    }

    public function harTitler()
    {
        return $this->har_titler;
    }

    public function erJobbeMed()
    {
        return !$this->hartitler();
    }

    public function harTekniskeBehov()
    {
        return $this->har_tekniske_behov;
    }
    public function harSjanger()
    {
        return $this->har_sjanger;
    }

    public function getFrist()
    {
        return $this->harTitler() ? 1 : 2;
    }

    public function __toString()
    {
        return $this->getNavn();
    }

    /**
     * Hvilken type tittel-objekt er dette
     *
     * @return String
     */
    public function getTittelClass()
    {
        switch ($this->getKey()) {
            case 'musikk':
                return 'Musikk';
            case 'dans':
                return 'Dans';
            case 'teater':
                return 'Teater';
            case 'litteratur':
                return 'Litteratur';
            case 'matkultur':
                return 'Matkultur';
            case 'utstilling':
                return 'Utstilling';
            case 'film':
            case 'video':
                return 'Film';
            case 'scene':
            case 'annet':
                return 'Annet';
        }
        throw new Exception(
            'Innslag-type ' . $this->getNavn() . ' har ikke titler',
            301001
        );
    }

    public static function validateClass($object)
    {
        return is_object($object) &&
            in_array(
                get_class($object),
                ['UKMNorge\Innslag\Type', 'innslag_type']
            );
    }
}
