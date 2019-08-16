<?php

namespace UKMNorge\Nettverk;

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
    private $administratorer;

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
                    $monstring = new monstring_v2($this->getForeignId());
                    $this->navn = $monstring->getNavn();
                    break;
            }
        }
        return $this->navn;
    }

    /**
     * Get ID of område
     *
     * @return Int $id
     */
    public function getId()
    {
        return $this->getType() . '_' . $this->id;
    }

    public function getForeignId()
    {
        return $this->id;
    }

    /**
     * Get the value of type
     */
    public function getType()
    {
        return $this->type;
    }

    public function getAdministratorer()
    {
        if (null == $this->administratorer) {
            $this->administratorer = new Administratorer($this->getType(), $this->getForeignId());
        }
        return $this->administratorer;
    }
}
