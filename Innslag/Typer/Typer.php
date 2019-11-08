<?php

namespace UKMNorge\Innslag\Typer;

use Exception;
#use UKMNorge\Innslag\Typer\Type;

require_once('UKM/Autoloader.php');

// KAN IKKE EXTENDE COLLECTION
// fordi numerisk ID er lik for alle underkategorier av scene 🤦🏼‍♂️
class Typer implements \Iterator
{
    private $var = array();
    static $all = null;
    static $allScene = null;
    static $skjulte = null;
    static $alle_inkludert_skjulte = null;
    /**
     * Har vi denne innslag typen?
     *
     * @param Any $object
     * @return Bool
     */
    public function har($object)
    {
        if (is_string($object)) {
            return $this->find($object);
        }
        return $this->find($object->getKey());
    }

    /**
     * Hent gitt innslag type
     *
     * @param Int $id
     * @return Type
     */
    public function get(Int $id)
    {
        return $this->find($id);
    }

    /**
     * Legg til en innslag type
     */
    public function leggTil($item)
    {
        $this->add($item);
    }

    /**
     * Fjern en innslag type
     *
     * @param String|Type $item
     * @return void
     */
    public function fjern($item)
    {
        $this->remove($item);
    }

    /**
     * Fjern en type fra collection
     *
     * @param String|Type $id
     * @return void
     */
    public function remove($id)
    {
        if (is_object($id)) {
            $id = $id->getKey();
        }

        foreach ($this->getAll() as $key => $val) {
            if ($id == $val->getKey()) {
                unset($this->var[$key]);
                return true;
            }
        }
        throw new Exception('Could not find and remove ' . $id, 110001);
    }

    /**
     * Finn objekt 
     *
     * @param Any $id
     * @return Item
     */
    public function find($id)
    {
        if( is_object($id) ) {
            $id = $id->getKey();
        }
        foreach ($this as $item) {
            if ($id == $item->getKey()) {
                return $item;
            }
        }
        return false;
    }

    /**
     * Har vi noen innslag i kategorien "jobbe med"?
     * 
     * @todo: refaktor og implementer harGruppe elns
     *
     * @return Bool
     */
    public function harJobbeMed()
    {
        return $this->har(Typer::getByName('nettredaksjon')) ||
            $this->har(Typer::getByName('konferansier')) ||
            $this->har(Typer::getByName('arrangor'));
    }

    /**
     * Har vi noen innslag i kategorien "vise frem"?
     *
     * @return Bool
     */
    public function harViseFrem() {
        foreach( $this->getAll() as $type ) {
            if( $type->harTitler() ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Hent innslag type fra numerisk Id / key
     * 
     * @deprecated APIv2
     * @see getByKey( String $key )
     *
     * @param Int $id
     * @param String $kategori
     * @return Type
     */
    static function getById(Int $id, String $kategori=null)
    {
        return self::load($id, $kategori);
    }

    /**
     * Hent innslag type fra key
     *
     * @param String $key
     * @return Type
     */
    static function getByKey(String $key)
    {
        return new Type( $key );
    }

    /**
     * Hent innslag type fra key 
     * 
     * @deprecated APIv2
     * @see getByKey( String $key )
     *
     * @param String $key
     * @return Type
     */
    static function getByName(String $key)
    {
        return static::getByKey($key);
    }

    /**
     * Hent alle typer
     *
     * @return Array<Type>
     */
    public function getAll()
    {
        return $this->var;
    }

    /**
     * Antall innslag typer vi har
     *
     * @return Int $antall
     */
    public function getAntall()
    {
        return sizeof($this->getAll());
    }


    /**
     * Hent alle typer som finnes (unntatt skjulte)
     * 
     * @see getAllInkludertSkjulteTyper()
     *
     * @return void
     */
    public static function getAllTyper()
    {   
        if (null == self::$all) {
            $alle = [
                'arrangor',
                'dans',
                'film',
                'konferansier',
                'litteratur',
                'matkultur',
                'musikk',
                'nettredaksjon',
                'scene',
                'teater',
                'utstilling'
            ];
            
            foreach( $alle as $id) {
                self::$all[] = self::getByKey($id);
            }

            self::$all = static::sort( self::$all );
        }

        return self::$all;
    }

    /**
     * Hent kun de skjulte typene
     *
     * @return Array<Type>
     */
    public static function getSkjulteTyper() {
        if( null == self::$skjulte ) {
            $skjulte = [
                'ressurs'
            ];
            foreach( $skjulte as $id ) {
                self::$skjulte[] = self::getByKey($id);
            }

            self::$skjulte = static::sort(self::$skjulte);
        }
        return self::$skjulte;
    }

    /**
     * Hent absolutt alle typer innslag, også de skjulte
     *
     * @return Array<Type>
     */
    public static function getAlleInkludertSkjulteTyper() {
        if( null == self::$alle_inkludert_skjulte ) {
            $alle = array_merge(
                static::getAllTyper(),
                static::getSkjulteTyper()
            );
            self::$alle_inkludert_skjulte = static::sort( $alle );
        }
        return self::$alle_inkludert_skjulte;
    }

    /**
     * Hent alle typer som er på scenen
     * 
     * Denne bør vi nok snart bort fra
     *
     * @return Array<Type>
     */
    static function getAllScene()
    {
        if (null == self::$allScene) {
            foreach (array('musikk', 'dans', 'teater', 'litteratur', 'annet') as $kategori) {
                self::$allScene[] = self::getById(1, $kategori);
            }
        }
        return self::$allScene;
    }

    public static function getStandardTyper() {
        $typer = static::getAllScene();
        $typer[] = Typer::getByKey('utstilling');
        $typer[] = Typer::getByKey('film');
        $typer = static::sort($typer);
        return $typer;
    }

    /**
     * Last inn en type Innslag
     *
     * @param Int $id
     * @param String $kategori
     * @return Type
     */
    static function load(Int $id, String $kategori = null)
    {
        if (!$kategori) {
            return new Type(static::_translate_id_to_key($id));
        }
        return new Type($kategori);
    }

    /**
     * Finn numerisk id fra gitt key
     *
     * @param String $key
     * @return Int $numeric_id
     */
    private static function _translate_key_to_id(String $key)
    {
        switch ($key) {
            case 'musikk':
            case 'dans':
            case 'teater':
            case 'litteratur':
            case 'scene':
                $bt_id = 1;
                break;
            case 'film':
            case 'video':
                $bt_id = 2;
                break;
            case 'utstilling':
                $bt_id = 3;
                break;
            case 'konferansier':
                $bt_id = 4;
                break;
            case 'nettredaksjon':
                $bt_id = 5;
                break;
            case 'matkultur':
                $bt_id = 6;
                break;
            case 'arrangor':
                $bt_id = 8;
                break;
            case 'sceneteknikk':
                $bt_id = 9;
                break;
            case 'annet':
                $bt_id = 1;
                break;
            case 'ressurs':
                $bt_id = 10;
                break;
            default:
                $bt_id = false;
        }
        return $bt_id;
    }

    /**
     * Oversett numerisk ID til reell ID
     * 
     * Støtter ikke ID:1, da den kan ha flere
     *
     * @param Int $id
     * @return String $key
     */
    private static function _translate_id_to_key(Int $id)
    {
        switch ($id) {
            case 2:
                return 'video';
            case 3:
                return 'utstilling';
            case 4:
                return 'konferansier';
            case 5:
                return 'nettredaksjon';
            case 6:
                return 'matkultur';
            case 8:
                return 'arrangor';
            case 9:
                return 'arrangor';
            case 10:
                return 'ressurs';
        }
        throw new Exception(
            'Ukjent innslag-type ' . $id,
            110002
        );
    }

    /**
     * Sorter et array Type-objekter
     *
     * @param Array<Type> $array
     * @return Array<Type> sortert
     */
    public static function sort( Array $array ) {
        usort( 
            $array, 
            function($left,$right) { 
                return $left->getNavn() > $right->getNavn();
            }
        );
        return $array;
    }

    /**
     * Legg til en type innslag fra ID
     *
     * @deprecated APIv2
     * @param Int $id
     * @return self
     */
    public function addById(Int $id)
    {
        return $this->add(self::getById($id));
    }

    /**
     * Legg til en innslag type
     *
     * @param Type $item
     * @return self
     */
    public function add($item)
    {
        if( !$this->find( $item ) ) {
            $this->var[] = $item;
        }
        return $this;
    }

    /**
     * Tell opp antall elementer
     *
     * @return Int Antall innslag typer
     */
    public function count()
    {
        return sizeof($this->var);
    }

    /**
     * Reset internal pointer
     * Ikke i bruk - kun krav fra \Iterator
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->var);
    }

    /**
     * Hent current 
     * Ikke i bruk - kun krav fra \Iterator
     *
     * @return Type
     */
    public function current()
    {
        $var = current($this->var);
        return $var;
    }

    /**
     * Hent key ?
     * Ikke i bruk - kun krav fra \Iterator
     *
     * @return Any something
     */
    public function key()
    {
        $var = key($this->var);
        return $var;
    }

    /**
     * Hent neste verdi
     * Ikke i bruk - kun krav fra \Iterator
     *
     * @return Any something
     */
    public function next()
    {
        $var = next($this->var);
        return $var;
    }

    /**
     * Er noe valid?
     * Ikke i bruk - kun krav fra \Iterator
     *
     * @return Any something
     */
    public function valid()
    {
        $key = key($this->var);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }
}
