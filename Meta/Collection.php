<?php

namespace UKMNorge\Meta;

use Exception;

require_once('UKM/Meta/ParentObject.php');
require_once('UKM/Meta/Value.php');

class Collection {
    private $parent;
    private $values;

    /**
     * Opprett ny Collection
     * 
     * @param ParentObject $parent
     */
    public function __construct( ParentObject $parent )
    {
        $this->parent = $parent;
    }

    /**
     * Hent verdi med navnet $key
     *
     * @param String $key
     * @return Value $value
     */
    public function get( $key ) {
        if( !isset( $this->values[ $key ] ) ) {
            $this->values[ $key ] = Value::loadFromKey( $this->getParent(), $key );
        }
        return $this->values[ $key ];
    }

    public function getValue( $key ) {
        return $this->get( $key )->getValue();
    }

    /**
     * Har dette objektet verdien $key?
     *
     * @param String $key
     * @return Bool $eksisterer_i_db
     */
    public function har( String $key ) {
        return $this->get( $key )->eksterer(); 
    }

    /**
     * Hent alle options for objektet
     *
     * @throws Exception not implemented
     */
    public function getAll() {
        throw new Exception('Noe mangler i MetaCollection');
    }

    /**
     * Opprett en collection basert på parent-rådata
     *
     * @param String $type
     * @param Int $id
     * @return Collection $meta_collection
     */
    public static function createByParentInfo( String $type, Int $id ) {
        return static::createByParent(
            new ParentObject( $type, $id )
        );
    }

    public static function createByParent( ParentObject $parent ) {
        return new Collection($parent);
    }

    /**
     * Hent parent-objektet
     */ 
    public function getParent()
    {
        return $this->parent;
    }
}