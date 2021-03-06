<?php

namespace UKMNorge;

use Iterator;
use Exception;

abstract class Collection implements Iterator
{
    private $var = array();
    public $id = null;
    private $loaded = false;

    /**
     * Legg til et element i collection
     *
     * @param any $item
     * @return self
     */
    public function add($item)
    {
        // Denne må bruke find, og ikke har,
        // da har kjører doLoad, og doLoad kjører
        // add, som kjører har() (infinite loop, altså)
        if (!$this->find($item)) {
            $this->var[] = $item;
        }
        return $this;
    }

    /**
     * Legg til et element i collection
     *
     * @see add()
     * @return self
     */
    public function leggTil($item)
    {
        return $this->add($item);
    }

    /**
     * Fjern et element fra collection
     *
     * @see remove()
     * @param mixed|string $object|$id
     * @throws Exception
     * @return bool
     */
    public function fjern($item)
    {
        return $this->remove($item);
    }

    /**
     * Er dette objektet i collection?
     *
     * @param mixed|string $object|$id
     * @return bool
     */
    public function har($object)
    {
        $this->_doLoad();
        if (is_string($object)) {
            return $this->find($object);
        }
        return $this->find($object->getId());
    }

    /**
     * Hent ett gitt objekt
     * 
     * Laster inn collection data hvis ikke dette allerede er gjort.
     *
     * @param mixed|string $object|$id
     * @return any item
     */
    public function get($id)
    {
        $this->_doLoad();
        return $this->find($id);
    }

    /**
     * Finn objekt 
     *
     * @param Any $id
     * @return Item
     */
    public function find($id)
    {
        if (is_object($id)) {
            $id = $id->getId();
        }
        foreach ($this as $item) {
            if ($id == $item->getId()) {
                return $item;
            }
        }
        return false;
    }

    /**
     * Hent alle
     * 
     * Laster inn collection data hvis ikke dette allerede er gjort.
     *
     * @return Array
     */
    public function getAll()
    {
        $this->_doLoad();
        return $this->var;
    }

    /**
     * Hent et array med id til objektene
     *
     * @return Array<Int>
     */
    public function getIdArray()
    {
        $array = [];

        foreach ($this->getAll() as $item) {
            $array[] = $item->getId();
        }

        return $array;
    }

    /**
     * Generisk __toArray (krever at alle objektene implementerer __toArray)
     *
     * @return Array
     */
    public function __toArray()
    {
        $array = [];

        foreach ($this->getAll() as $item) {
            $array[] = $item->__toArray();
        }
        return $array;
    }

    /**
     * Hjelpefunksjon for å trigge innlasting av objekter
     * 
     * Hvis extending class har _load-funksjon, kjøres denne. Ellers ingenting.
     *
     * @return bool
     */
    private function _doLoad()
    {
        if ($this->loaded) {
            return true;
        }
        if (method_exists(get_called_class(), '_load')) {
            $this->_load();
        }
        $this->loaded = true;
        return true;
    }

    /**
     * Antall elementer i collection
     *
     * @return Int $antall
     */
    public function getAntall()
    {
        return sizeof($this->getAll());
    }

    /**
     * Fjern et element fra collection
     *
     * @param mixed|string $object|$id
     * @throws Exception
     * @return bool
     */
    public function remove($id)
    {
        if (is_object($id)) {
            $id = $id->getId();
        }

        foreach ($this->getAll() as $key => $val) {
            if ($id == $val->getId()) {
                unset($this->var[$key]);
                return true;
            }
        }
        throw new Exception('Could not find and remove ' . $id, 110001);
    }

    /**
     * Hent Collection ID
     * 
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sett collection ID
     *
     * @return  self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }


    /**
     * STUFF KREVD AV Iterable
     */

    public function first()
    {
        if (isset(array_values($this->var)[0])) {
            return array_values($this->var)[0];
        }
    }
    
    public function last()
    {
        // TODO: Untested!!!!
        return array_slice($this->var, -1)[0];
    }

    public function count()
    {
        return sizeof($this->var);
    }

    public function rewind()
    {
        reset($this->var);
    }

    public function current()
    {
        $var = current($this->var);
        return $var;
    }

    public function key()
    {
        $var = key($this->var);
        return $var;
    }

    public function next()
    {
        $var = next($this->var);
        return $var;
    }

    public function valid()
    {
        $key = key($this->var);
        $var = ($key !== NULL && $key !== FALSE);
        return $var;
    }

    public function reset()
    {
        $this->var = [];
        $this->loaded = false;
    }

    public function __construct()
    {
    }
}
