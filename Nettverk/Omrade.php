<?php

namespace UKMNorge\Nettverk;

require_once('UKM/Autoloader.php');

use Exception;
use UKMNorge\Arrangement\Arrangementer;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Filter;
use UKMNorge\Arrangement\Kommende;
use UKMNorge\Arrangement\Kontaktperson\Kontaktperson;
use UKMNorge\Arrangement\Load;
use UKMNorge\Arrangement\Tidligere;
use UKMNorge\Geografi\Fylker;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Nettverk\Administratorer;
use UKMNorge\Nettverk\Proxy\Kontaktperson as KontaktpersonProxy;
use UKMNorge\Nettverk\Proxy\KontaktpersonSamling as KontaktpersonSamlingProxy;

class Omrade
{
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
    private $kontaktpersoner = null;
    private $arrangementer = null;
    private $arrangementer_filter = false;
    private $arrangementer_kommende = null;
    private $arrangementer_tidligere = null;
    private $arrangementer_aktuelle = null;
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
     * @return String
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
     * Hent en lenke til dette området
     *
     * @return String full url
     */
    public function getLink()
    {
        if ($this->getType() == 'fylke') {
            return $this->getFylke()->getLink(false);
        }
        if ($this->getType() == 'kommune') {
            return $this->getKommune()->getLink();
        }
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
     * @param Filter $filter
     * @return Arrangementer
     */
    public function getArrangementer(Filter $filter = null)
    {
        // Oppdater hvis nytt filter
        if ($this->arrangementer_filter === false || $filter != $this->arrangementer_filter) {
            $this->arrangementer_filter = $filter;
            $this->arrangementer = Load::byOmradeInfo('eier-' . $this->getType(), (int) $this->getForeignId(), $filter);
        }

        return $this->arrangementer;
    }

    /**
     * Hent områdets kommende arrangement
     *
     * @param Filter $filter
     * @return Arrangementer
     */
    public function getKommendeArrangementer(Filter $filter = null)
    {
        if (!isset($this->arrangementer_kommende)) {
            $this->arrangementer_kommende = Kommende::byOmradeInfo('eier-' . $this->getType(), (int) $this->getForeignId(), $filter);
        }
        return $this->arrangementer_kommende;
    }

    /**
     * Hent områdets aktuelle arrangement (frem i tid, eller fra årets sesong)
     *
     * @param Filter $filter
     * @return Arrangementer
     */
    public function getAktuelleArrangementer(Filter $filter = null)
    {
        if (!isset($this->arrangementer_aktuelle)) {
            if (is_null($filter)) {
                $filter = new Filter();
            }

            if (date('n') < 8) {
                $season_one = (int) date('Y');
                $season_two = (int) date('Y') - 1;
            } else {
                $season_one = (int) date('Y');
                $season_two = (int) date('Y') + 1;
            }
            $filter->sesong([$season_one, $season_two]);
            $this->arrangementer_aktuelle = Load::byOmradeInfo('eier-' . $this->getType(), (int) $this->getForeignId(), $filter);
        }
        return $this->arrangementer_aktuelle;
    }

    /**
     * Hent områdets tidligere arrangement
     *
     * @param Filter $filter
     * @return Arrangementer
     */
    public function getTidligereArrangementer(Filter $filter = null)
    {
        if (!isset($this->arrangementer_tidligere)) {
            $this->arrangementer_tidligere = Tidligere::byOmradeInfo('eier-' . $this->getType(), (int) $this->getForeignId(), $filter);
        }
        return $this->arrangementer_tidligere;
    }


    /**
     * Hent hvilket fylke området tilhører
     *
     * @throws Exception
     * @return Fylke
     */
    public function getFylke()
    {
        if (null == $this->fylke) {
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
    public function getKommune()
    {
        if (null == $this->kommune) {
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
     * @param Bool inkluder skjulte
     * @return KontakpersonSamling
     */
    public function getKontaktpersoner(Bool $inkluder_skjulte = false)
    {
        if ($this->kontaktpersoner == null) {
            $this->_loadKontaktpersoner($inkluder_skjulte);
        }
        return $this->kontaktpersoner;
    }

    /**
     * Last inn kontaktpersoner
     *
     * @param Bool $inkluder_skjulte
     * @return void
     */
    private function _loadKontaktpersoner(Bool $inkluder_skjulte = false)
    {
        $this->kontaktpersoner = new KontaktpersonSamlingProxy();

        // Hent områdets (arrangementets hovedeier) administratorer
        if ($this->getAdministratorer()->getAntall() > 0) {
            foreach ($this->getAdministratorer()->getAll() as $admin) {
                if (!$inkluder_skjulte && !$admin->erKontaktperson($this)) {
                    continue;
                }
                try {
                    $kontakt = Kontaktperson::getByAdminId($admin->getId());
                } catch (Exception $e) {
                    if ($e->getCode() != 111001) {
                        throw $e;
                    }
                    $kontakt = new KontaktpersonProxy($admin);
                }
                $this->kontaktpersoner->add($kontakt);
            }
            return;
        }

        // Hvis det er en kommune uten admins, hent fylkets kontaktpersoner
        if ($this->getType() == 'kommune') {
            $omrade = Omrade::getByFylke($this->getFylke()->getId());
            foreach ($omrade->getAdministratorer()->getAll() as $admin) {
                $this->kontaktpersoner->add(new KontaktpersonProxy($admin));
            }
        }
    }
}
