<?php

namespace UKMNorge\Geografi;

use Exception;
use UKMNorge\Nettverk\Administratorer;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class Fylke
{
    var $id = null;
    var $link = null;
    var $navn = null;
    var $attributes = null;
    var $kommuner = null;
    var $nettverk_omrade = null;
    var $fake = false;
    var $active = false;

    public function __construct(Int $id, String $link, String $name, Bool $active, Bool $fake = false)
    {
        $this->id = (int) $id;
        $this->link = $link;
        $this->navn = $name;
        $this->active = $active;
        $this->fake = $fake;
    }

    /**
     * Hent fylkets ID
     *
     * @return Int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent link til fylkessiden
     *
     * @param Bool $pathOnly (bruk true f.o.m. 2019)
     * @return String $link
     */
    public function getLink(Bool $pathOnly = true)
    {
        if ($pathOnly) {
            return $this->link;
        }
        return '//' . UKM_HOSTNAME . $this->getPath();
    }

    /**
     * Hent fylkessidens path (ikke link)
     *
     * @return String
     */
    public function getPath()
    {
        return '/' . trim($this->link, '/') . '/';
    }

    /**
     * Hent fylkets navn
     *
     * @return String $navn
     */
    public function getNavn()
    {
        if (null == $this->navn) {
            return 'ukjent';
        }

        return $this->navn;
    }

    /**
     * Er fylket falskt
     * 
     * Falske fylker eksisterer kun i UKM-systemet. Snakk om å være inkluderende.
     *
     * @return Bool $fake
     */
    public function erFalskt()
    {
        return $this->fake;
    }

    /**
     * Er fylket aktivt?
     * 
     * Eller har det gått ut på dato?
     *
     * @return Bool $active
     */
    public function erAktiv()
    {
        return $this->active;
    }

    /**
     * Er fylket aktivt?
     * @alias erAktiv
     *
     * @return Bool $active
     */
    public function erAktivt()
    {
        return $this->erAktiv();
    }

    /**
     * Er dette fylket Oslo?
     * 
     * I og for seg ikke såå nøye å vite, men kommunene liker å vite det,
     * da vi lister ut bydeler og ikke kommuner for Oslo i systemet.
     *
     * @return Bool $er_oslo
     */
    public function erOslo()
    {
        return $this->getId() == 3;
    }

    /**
     * Hent geografisk administrasjons-område for fylket
     *
     * @return UKMNorge\Nettverk\Omrade
     */
    public function getNettverkOmrade()
    {
        if ($this->nettverk_omrade == null) {
            $this->nettverk_omrade = Omrade::getByFylke(
                (int) $this->getId()
            );
        }
        return $this->nettverk_omrade;
    }

    /**
     * Sett attributt (som følger objektet i scriptets runtime)
     * 
     * Sett egenskaper som for enkelhets skyld kan følge mønstringen et lite stykke
     * Vil aldri kunne lagres
     *
     * @param string $key
     * @param $value
     *
     * @return innslag
     **/
    public function setAttr($key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Hent attributt (som kun følger objektet i scriptets runtime)
     *
     * @param string $key
     *
     * @return value
     **/
    public function getAttr($key)
    {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : false;
    }


    /**
     * Hent alle kommuner tilhørende fylket
     *
     * @return kommuner $kommuner;
     */
    public function getKommuner()
    {
        if (null == $this->kommuner) {
            $this->kommuner = new Kommuner();

            $sql = new Query(
                Kommune::getLoadQuery() .
                    " 
                WHERE `idfylke` = '#fylke'
                AND `active` = 'true'
                ORDER BY `name` ASC",
                [
                    'fylke' => $this->getId()
                ]
            );
            $res = $sql->run();

            if ($res) {
                while ($r = Query::fetch($res)) {
                    $kommune = new Kommune($r);
                    // Ekskluderer gjeste-kommuner
                    if ($kommune->getId() != ($this->getId() . '90')) {
                        $this->kommuner->add($kommune);
                    }
                }
            }
        }
        return $this->kommuner;
    }

    /**
     * getKommunerUtenGjester
     * 
     * fjerner gjestekommunen fra kommune-lista og returnerer forøvrig getKommuner
     *
     * @return Array kommuner
     **/
    public function getKommunerUtenGjester()
    {
        $kommuner = [];
        foreach ($this->getKommuner() as $kommune) {
            if ($kommune->getId() != ($this->getId() . '90')) {
                $kommuner[] = $kommune;
            }
        }
        return $kommuner;
    }

    /**
     * Hvis fylket plutselig blir en string, så er navnet det viktigste.
     *
     * @return String $navn
     */
    public function __toString()
    {
        return $this->getNavn();
    }

    /**
     * getURLsafe
     * 
     * Alias av getLink for consistency kommune.class
     * @return string link
     **/
    public function getURLsafe()
    {
        return $this->getLink();
    }


    /**
     * Har fylket blitt erstattet av et annet?
     *
     * @return Bool
     */
    public function erOvertatt()
    {
        try {
            Fylker::getOvertattAv($this->getId());
            return true;
        } catch( Exception $e) {}
        
        return false;
    }

    /**
     * Hvilket fylke har overtatt for dette?
     *
     * @return Fylke
     */
    public function getOvertattAv()
    {
        if (!$this->erOvertatt()) {
            throw new Exception(
                $this->getNavn() . ' er fortsatt aktivt, og ikke overtatt av et annet fylke',
                163001
            );
        }
        return Fylker::getOvertattAv($this->getId());
    }

    /**
     * Har dette fylket overtatt for et annet?
     *
     * @return Bool
     */
    public function harOvertatt()
    {
        try {
            Fylker::getOvertattFor( $this->getId() );
            return true;
        } catch( Exception $e ){}

        return false;
    }

    /**
     * Hent hvilke fylker dette fylket har overtatt for
     *
     * @throws Exception
     * @return Array<Fylke>
     */
    public function getOvertattFor()
    {
        if (!$this->harOvertatt()) {
            throw new Exception(
                $this->getNavn() . ' har ikke overtatt for et annet fylke.',
                163002
            );
        }

        return Fylker::getOvertattFor( $this->getId() );
    }

    /**
     * Er gitt objekt gyldig Fylke-objekt?
     *
     * @param Any $object
     * @return Bool
     */
    public static function validateClass($object)
    {
        return is_object($object) &&
            in_array(
                get_class($object),
                ['UKMNorge\Geografi\Fylke', 'fylke']
            );
    }
}
