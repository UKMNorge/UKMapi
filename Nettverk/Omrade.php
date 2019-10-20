<?php

namespace UKMNorge\Nettverk;

require_once('UKM/Autoloader.php');

use Exception;
use UKMNorge\Arrangement\Arrangementer;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Geografi\Fylker;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Nettverk\Administratorer;
use UKMNorge\Nettverk\Proxy\Kontaktperson as KontaktpersonProxy;
use UKMNorge\Nettverk\Proxy\KontaktpersonSamling as KontaktpersonSamlingProxy;

class Omrade
{
    private $kontaktpersoner = null;

    /**
     * Hent nasjonalt område
     * (WHY?)
     *
     * @return Omrade
     */
    public static function getByLand()
    {
        return static::getByType('land', 0);
    }
    /**
     * Hent fylke-område
     *
     * @param Int $id
     * @return Omrade
     */
    public static function getByFylke(Int $id)
    {
        return static::getByType('fylke', $id);
    }
    /**
     * Hent kommune-område
     *
     * @param Int $id
     * @return Omrade
     */
    public static function getByKommune(Int $id)
    {
        return static::getByType('kommune', $id);
    }
    /**
     * Hent arrangementets overordnede område
     *
     * @param Int $id
     * @return Omrade
     */
    public static function getByMonstring(Int $id)
    {
        return static::getByType('monstring', $id);
    }

    /**
     * Hent område fra type og id
     *
     * @param String $type
     * @param Int $id
     * @return Omrade
     */
    public static function getByType(String $type, Int $id)
    {
        return new Omrade($type, $id);
    }

    private $type = null;
    private $id = 0;
    private $navn = null;
    private $administratorer = null;
    private $arrangementer = [];
    private $fylke = null;
    private $kommune = null;

    public function __construct(String $type, Int $id)
    {
        $this->type = $type;
        $this->id = $id;

        switch ($this->getType()) {
            case 'land':
                $this->navn = 'Norge';
                break;
            case 'fylke':
                $this->fylke = Fylker::getById($this->getForeignId());
                $this->navn = $this->fylke->getNavn();
                break;
            case 'kommune':
                $this->kommune = new Kommune($this->getForeignId());
                $this->fylke = $this->kommune->getFylke();
                $this->navn = $this->kommune->getNavn();
                break;
            case 'monstring':
                $monstring = new Arrangement($this->getForeignId());
                $this->navn = $monstring->getNavn();
                break;
        }
    }

    /**
     * Hent områdets navn
     *
     * @return void
     */
    public function getNavn()
    {
        return $this->navn;
    }

    /**
     * Hent områdets ID (concat string av type + id)
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
     * @return Administratorer
     */
    public function getAdministratorer()
    {
        if (null == $this->administratorer) {
            $this->administratorer = new Administratorer($this->getType(), $this->getForeignId());
        }
        return $this->administratorer;
    }

    /**
     * Hent arrangementer for området
     *
     * @param Int $season
     * @return Array<Arrangementer>
     */
    public function getArrangementer( Int $season ) {
        if ( !isset( $this->arrangementer[ $season ] ) ) {
            $this->arrangementer[ $season ] = new Arrangementer(
                $season,
                'eier-'.$this->getType(),
                (int) $this->getForeignId()
            );
        }
        
        return $this->arrangementer[ $season ];
    }


    /**
     * Hent hvilket fylke området tilhører
     *
     * @throws Exception
     * @return Fylke
     */
    public function getFylke() {
        if( null == $this->fylke ) {
            throw new Exception(
                'Dette området tilhører ikke et fylke'
            );
        }
        return $this->fylke;
    }

    /**
     * Hent hvilken kommune området tilhører
     *
     * @throws Exception
     * @return Kommune
     */
    public function getKommune() {
        if( null == $this->kommune ) {
            throw new Exception(
                'Dette området tilhører ikke en kommune'
            );
        }
        return $this->kommune;
    }

    /**
     * Hent sesong
     * (WHY?)
     * 
     * @return Int $sesong
     */ 
    public function getSeason()
    {
        return $this->season;
    }

    /**
     * Sett sesong
     * 
     * @param Int $season
     * @return self
     */ 
    public function setSeason($season)
    {
        $this->season = $season;

        return $this;
    }

    /**
     * Hent kontaktpersoner for området, eller foreldre-området
     *
     * @return KontakpersonSamling
     */
    public function getKontaktpersoner() {
        if( $this->kontaktpersoner == null ) {
            $this->_loadKontaktpersoner();
        }
        return $this->kontaktpersoner;
    
    }

    private function _loadKontaktpersoner() {
        $this->kontaktpersoner = new KontaktpersonSamlingProxy();

        // Hent områdets (arrangementets hovedeier) administratorer
        if( $this->getAdministratorer()->getAntall() > 0 ) {
            foreach( $this->getAdministratorer()->getAll() as $admin ) {
                $this->kontaktpersoner->add( new KontaktpersonProxy( $admin ) );
            }
            return ;
        }

        // Hvis det er en kommune uten admins, hent fylkets kontaktpersoner
        if( $this->getType() == 'kommune' ) {
            $omrade = Omrade::getByFylke( $this->getFylke()->getId() );
            foreach( $omrade->getAdministratorer()->getAll() as $admin ) {
                $this->kontaktpersoner->add( new KontaktpersonProxy( $admin ) );
            }
        }
    }
}
