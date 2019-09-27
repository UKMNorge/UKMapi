<?php

namespace UKMNorge\Arrangement;

use kommune, fylker;
use UKMNorge\Database\SQL\Query;

require_once('UKM/fylker.class.php');
require_once('UKM/kommune.class.php');

class Eier
{

    private $type;
    private $id;
    private $name;
    private $parent;

    /**
     * Opprett ny eier
     *
     * @param String $type
     * @param Int $id
     */
    public function __construct(String $type, Int $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * Hent eier-id
     * 
     * @return Int $id
     */ 
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent eiertype
     * 
     * @return String $type
     */ 
    public function getType()
    {
        return $this->type;
    }

    /**
     * Hent eierens navn
     * 
     * @return String $navn
     */ 
    public function getName()
    {
        if( null == $this->name ) {
            return $this->getParent()->getNavn();
        }

        return $this->name;
    }

    /**
     * Hent eierens foreldre- (reelle) objekt
     * 
     * @return kommune|fylke $parent
     */ 
    public function getParent()
    {
        if( null == $this->parent ) {
            switch( $this->getType() ) {
                case 'kommune': 
                    $this->parent = new kommune( $this->getId() );
                    break;
                case 'fylke':
                    $this->parent = fylker::getById( $this->getId() );
                    break;
            }
            $this->name = $this->parent->getNavn();
        }
        return $this->parent;
    }
    

    /**
     * Hent Eier-objekt fra en mønstring
     *
     * @param Int $pl_id
     * @return void
     */
    public static function loadFromPlId( Int $pl_id ) {
        $query = new Query(
            "SELECT `pl_owner_kommune`, `pl_owner_fylke`
            FROM `smartukm_place`
            WHERE `pl_id` = '#pl_id'",
            [
                'pl_id' => $pl_id
            ]
        );
        $db_row = $query->run('array');
        if( !$db_row ) {
            throw new Exception(
                'Klarte ikke å finne mønstring '. $pl_id,
                159001  
            );
        }
        
        return static::loadFromKommuneFylkeData( $db_row['pl_owner_kommune'], $db_row['pl_owner_fylke'] );
    }

    public static function loadFromKommuneFylkeData( Int $owner_kommune, Int $owner_fylke ) {
        return new Eier(
            $owner_kommune == 0 ? 'fylke' : 'kommune',
            $owner_kommune == 0 ? $owner_fylke : $owner_kommune
        );
    }
}
