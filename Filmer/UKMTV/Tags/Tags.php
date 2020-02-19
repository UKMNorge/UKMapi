<?php

namespace UKMNorge\Filmer\UKMTV\Tags;

use UKMNorge\Collection;
use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Geografi\Fylker;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Innslag\Innslag;

class Tags extends Collection {
    private $personer = null;
    const ALLOW_MANY = ['person' => 'Personer'];
    const ARRANGEMENT_TYPER = [1 => 'kommune', 2 => 'fylke', 3 => 'land'];

    /**
     * Last inn alle tags
     *
     * @return Tags
     */
    public static function createFromString(String $tags)
    {
        $tags = explode(
            '|',
            $tags
        );
        $collection = new Tags();
        foreach ($tags as $string) {
            if (strpos($string, ':') === false) {
                continue;
            }
            $tag = explode(':', $string);
            if ( static::erMultiTag($tag[0])) {
                /* 
                * Med denne støtter vi flere tags hvor samme film kan ha
                * flere verdier (foreign_keys) for samme tag 👇🏼
                  $this->getManyCollectionFor($tag[0])->add( new $class( $tag[1] ) );
                *
                * inntil videre bruker vi dog bare linja under for å 
                * støtte personer-tags 👇🏼
                */
                $this->getPersoner()->add(new Person($tag[1]));
            } else {
                $collection->add(new Tag($tag[0], $tag[1]));
            }
        }
        return $collection;
    }

    /**
     * Hent absolutt alle tags
     *
     * @return Array<Tag>
     */
    public function getAllInkludertManyCollections() {
        $alle = $this->getAll();
        foreach( static::ALLOW_MANY as $many_id => $many_class ) {
            $alle = array_merge(
                $this->getManyCollectionFor($many_id)->getAll()
            );
        }
        return $alle;
    }

    /**
     * Hent alle collections hvor tags kan ha flere foreign_keys per type
     *
     * @return Array<Many>
     */
    public function getManyCollections() {
        $alle = [];
        foreach( static::ALLOW_MANY as $many_id ) {
            $alle[] = $this->getManyCollectionFor($many_id);
        }
        return $alle;
    }

    /**
     * Hent Collection for en tag som kan ha flere foreign_keys
     * per film
     *
     * @param String $tag_type
     * @return Many
     */
    public function getManyCollectionFor( String $tag_type ) {
        $class = static::ALLOW_MANY[ $tag_type ];
        $funct = 'get'.$class;
        return $this->$funct();
    }

    /**
     * Kan denne tag-typen har flere forskjellige foreign_keys
     * per film?
     *
     * @param String $key
     * @return bool
     */
    public static function erMultiTag( String $key ) {
        return in_array($key, static::ALLOW_MANY);
    }

    /**
     * Legg til en ny tag i collection (lagrer ikke)
     * 
     * Samme som $this->add( new Tag( $type, $value ) );
     *
     * @param String $type
     * @param Int $value
     * @return this
     */
    public function opprett(String $type, Int $value)
    {
        $this->add(new Tag($type, $value));
        return $this;
    }

    /**
     * Hent verdi av en tag
     *
     * @param String $key
     * @return Int 
     */
    public function getValue(String $key)
    {
        if (!$this->har($key)) {
            return false;
        }
        return $this->get($key)->getValue();
    }

    /**
     * Hent innslag-objektet (hvis vi har b-tag'en)
     *
     * @return Innslag
     */
    public function getInnslag()
    {
        if (!$this->har('innslag')) {
            throw new Exception(
                'Filmen er ikke tilknyttet et innslag',
                115002
            );
        }
        if ($this->har('arrangement')) {
            return $this->getArrangement()->getInnslag()->get(
                $this->get('innslag'),
                true // også hvis uferdig påmelding
            );
        }
        return new Innslag($this->getValue('innslag'));
    }

    /**
     * Hent arrangement-objektet (hvis vi har pl-tag'en)
     *
     * @return Arrangement
     */
    public function getArrangement()
    {
        if (!$this->har('arrangement')) {
            throw new Exception(
                'Filmen er ikke tilknyttet et arrangement',
                115002
            );
        }
        return new Arrangement($this->getValue('arrangement'));
    }

    /**
     * Hent hvilken type arrangement dette er
     *
     * @return String
     */
    public function getArrangementType() {
        if( $this->har('arrangement_type') ) {
            return static::getArrangementTypeFromId( $this->getValue('arrangement_type') );
        }
        return $this->getArrangement()->getType();
    }

    /**
     * Hent sesong 
     *
     * @return Int sesong
     */
    public function getSesong()
    {
        if (!$this->har('sesong')) {
            throw new Exception(
                'Filmen er ikke tilknyttet en gitt sesong',
                115003
            );
        }
        return intval($this->getValue('sesong'));
    }

    /**
     * Hent kommune
     *
     * @return void
     */
    public function getKommune()
    {
        if (!$this->har('kommune')) {
            throw new Exception(
                'Filmen er ikke tilknyttet en kommune',
                115004
            );
        }
        return new Kommune(intval($this->getValue('kommune')));
    }

    /**
     * Hent fylke
     *
     * @return void
     */
    public function getFylke()
    {
        if (!$this->har('fylke')) {
            throw new Exception(
                'Filmen er ikke tilknyttet et fylke',
                115005
            );
        }
        return Fylker::getById(intval($this->getValue('fylke')));
    }

    /**
     * Hent personerCollection
     * 
     * @return Many 
     */
    public function getPersoner()
    {
        if (null == $this->personer) {
            /**
             * Many er en generisk collection, slik som
             * Personer() også ville vært. Many er her
             * for at vi senere kan støtte flere tags 
             * hvor samme film kan ha flere verdier
             * (foreign_keys) for samme tag.
             */
            $this->personer = new Many();
        }
        return $this->personer;
    }

    /**
     * Hent hvilken type arrangement filmen er tilknyttet, ut fra arrangement_type-tag
     *
     * @param Int $id
     * @return String
     */
    public static function getArrangementTypeFromId( Int $id ) {
        if( !isset(static::ARRANGEMENT_TYPER[$id])) {
            throw new Exception(
                'Ukjent arrangement-type '. $id,
                115006
            );
        }
        return static::ARRANGEMENT_TYPER[$id];
    }

    /**
     * Hent hvilken ID som skal lagres på arrangement_type-tag'en
     *
     * @param String $type
     * @return Int
     */
    public static function getArrangementTypeId( String $type ) {
        return array_search( $type, static::ARRANGEMENT_TYPER );
    }
}
