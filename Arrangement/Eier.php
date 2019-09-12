<?php

namespace UKMNorge\Arrangement;

use kommune, fylker;

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
    
}
