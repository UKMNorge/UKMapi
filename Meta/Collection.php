<?php

namespace UKMNorge\Meta;

use Exception;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Meta/ParentObject.php');
require_once('UKM/Meta/Value.php');

class Collection
{
    private $parent;
    private $values;
    private $loaded = false;

    /**
     * Opprett ny Collection
     * 
     * @param ParentObject $parent
     */
    public function __construct(ParentObject $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Hent verdi med navnet $key
     *
     * @param String $key
     * @return Value $value
     */
    public function get($key)
    {
        if (!isset($this->values[$key])) {
            $this->values[$key] = Value::loadFromKey($this->getParent(), $key);
        }
        return $this->values[$key];
    }

    public function getValue($key)
    {
        return $this->get($key)->getValue();
    }

    /**
     * Har dette objektet verdien $key?
     *
     * @param String $key
     * @return Bool $eksisterer_i_db
     */
    public function har(String $key)
    {
        return $this->get($key)->eksterer();
    }

    /**
     * Hent alle options for objektet
     *
     * @return Array<Value>
     */
    public function getAll()
    {

        if (!$this->loaded) {
            $this->_load();
        }

        return $this->values;
    }

    /**
     * Opprett en collection basert på parent-rådata
     *
     * @param String $type
     * @param Int $id
     * @return Collection $meta_collection
     */
    public static function createByParentInfo(String $type, Int $id)
    {
        return static::createByParent(
            new ParentObject($type, $id)
        );
    }

    /**
     * Opprett en samling for ParentObjekt
     * 
     * @param ParentObject $parent
     * @return Collection
     */
    public static function createByParent(ParentObject $parent)
    {
        return new Collection($parent);
    }

    /**
     * Hent parent-objektet
     * 
     * @return ParentObject
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Laster inn alle meta-verdier for gitt parentObject
     *
     * @return void
     */
    private function _load()
    {
        $this->loaded = true;
        $sql = new Query(
            "SELECT `value`, `name`, `id`
            FROM `ukm_meta`
            WHERE `parent_type` = '#parent_type'
            AND `parent_id` = '#parent_id'",
            [
                'parent_type' => $this->getParent()->getType(),
                'parent_id' => $this->getParent()->getId(),
            ]
        );

        $res = $sql->getResults();

        while ($row = Query::fetch($res)) {
            $key = $row['name'];
            // Kun verdier vi ikke har fra før skal loades, 
            // for de andre kan vi anta at en nylig satt verdi er mer
            // riktig enn den som er i databasen.
            //
            // Hvis ikke vil ikke lagring fungere (med mindre man kjører
            // getAll() før man starter å sette oppdaterte verdier).
            if (!isset($this->values[$key])) {
                $this->values[$key] = Value::loadFromData(
                    $this->getParent(),
                    $key,
                    $row
                );
            }
        }
    }
}
