<?php

namespace UKMNorge\Nettverk;

require_once('UKM/Arrangement/Arrangementer.collection.php');

use UKMNorge\Arrangement\Arrangementer;
use UKMNorge\Arrangement\Arrangement;
use Fylker;
use kommune;

class Omrade
{

    public static function getByLand()
    {
        return static::getByType('land', 0);
    }
    public static function getByFylke(Int $id)
    {
        return static::getByType('fylke', $id);
    }
    public static function getByKommune(Int $id)
    {
        return static::getByType('kommune', $id);
    }
    public static function getByMonstring(Int $id)
    {
        return static::getByType('monstring', $id);
    }

    public static function getByType(String $type, Int $id)
    {
        return new Omrade($type, $id);
    }

    private $type = null;
    private $id = 0;
    private $navn = null;
    private $administratorer = null;
    private $arrangementer = [];

    public function __construct(String $type, Int $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    public function getNavn()
    {
        if ($this->navn == null) {
            switch ($this->getType()) {
                case 'land':
                    $this->navn = 'Norge';
                    break;
                case 'fylke':
                    require_once('UKM/fylker.class.php');
                    $this->navn = Fylker::getById($this->getForeignId())->getNavn();
                    break;
                case 'kommune':
                    $kommune = new kommune($this->getForeignId());
                    $this->navn = $kommune->getNavn();
                    break;
                case 'monstring':
                    $monstring = new Arrangement($this->getForeignId());
                    $this->navn = $monstring->getNavn();
                    break;
            }
        }
        return $this->navn;
    }

    /**
     * Områdets ID (concat string av type + id)
     *
     * @return String concat( $type_$id )
     */
    public function getId()
    {
        return strtolower($this->getType()) . '_' . $this->id;
    }

    /**
     * Hent områdets faktiske ID (foreign ID)
     *
     * @return Int $id
     */
    public function getForeignId()
    {
        return $this->id;
    }

    /**
     * Hvilken type område er dette?
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Hent administratorer for området
     *
     * @return void
     */
    public function getAdministratorer()
    {
        if (null == $this->administratorer) {
            $this->administratorer = new Administratorer($this->getType(), $this->getForeignId());
        }
        return $this->administratorer;
    }

    public function getArrangementer( Int $season ) {
        if ( !isset( $this->arrangementer[ $season ] ) ) {
            $this->arrangementer[ $season ] = new Arrangementer(
                $season,
                $this->getType(),
                (int) $this->getForeignId()
            );
        }
        return $this->arrangementer;
    }

    /**
     * Get the value of season
     */ 
    public function getSeason()
    {
        return $this->season;
    }

    /**
     * Set the value of season
     *
     * @return  self
     */ 
    public function setSeason($season)
    {
        $this->season = $season;

        return $this;
    }
}
