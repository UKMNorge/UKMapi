<?php

namespace UKMNorge\Arrangement\Program;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Innslag\Samling;
use UKMNorge\Database\SQL\Query;

use Exception;
use DateTime, DateInterval;

require_once('UKM/Autoloader.php');

class Hendelse
{
    // Midlertidig hack i påvente av omskriving
    var $id = null;
    var $navn = null;
    var $monstring_id = null;
    var $monstring = null;
    var $start = null;
    var $start_datetime = null;
    var $synlig_i_rammeprogram = null;
    var $synlig_detaljprogram = null;
    var $synlig_oppmotetid = false;
    var $oppmote_for = null;
    var $oppmote_delay = null;
    var $intern = false;
    var $type = null;
    var $type_post_id = null;
    var $type_category_id = null;
    var $beskrivelse = null;
    var $farge = null;
    var $fremhevet = null;
    var $innslag = null;

    var $collection_innslag = null;

    public function __construct($data){

        if( is_numeric( $data ) ) {
            $data = $this->_loadFromId( $data );
        }

        $this->setId($data['c_id']);
        $this->setNavn($data['c_name']);
        $this->setStart($data['c_start']);
        $this->setSted($data['c_place']);
        $this->setMonstringId($data['pl_id']);
        $this->setSynligRammeprogram('true' == $data['c_visible_program']);
        $this->setSynligDetaljprogram('true' == $data['c_visible_detail']);
        $this->setSynligOppmotetid('true' == $data['c_visible_oppmote']);
        $this->setOppmoteFor($data['c_before']);
        $this->setOppmoteDelay($data['c_delay']);
        $this->setType($data['c_type']);
        $this->setTypePostId($data['c_type_post_id']);
        $this->setTypeCategoryId($data['c_type_category_id']);
        $this->setIntern('true' == $data['c_intern']);
        $this->setBeskrivelse($data['c_beskrivelse']);
        $this->setFarge($data['c_color']);
        $this->setFremhevet('true' == $data['c_fremhevet']);
    }

    public function erFremhevet()
    {
        return $this->getFremhevet();
    }
    public function getFremhevet()
    {
        return $this->fremhevet;
    }
    public function setFremhevet($bool)
    {
        $this->fremhevet = $bool;
        return $this;
    }

    public function getFarge()
    {
        return $this->farge;
    }
    public function setFarge($farge)
    {
        $this->farge = $farge;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setBeskrivelse($beskrivelse)
    {
        $this->beskrivelse = $beskrivelse;
        return $this;
    }
    public function getBeskrivelse()
    {
        return $this->beskrivelse;
    }

    public function setIntern($intern)
    {
        $this->intern = $intern;
        return $this;
    }
    public function erIntern()
    {
        return $this->intern;
    }
    public function getIntern()
    {
        return $this->erIntern();
    }

    public function getTypePostId()
    {
        return $this->type_post_id;
    }
    public function setTypePostId($post_id)
    {
        $this->type_post_id = $post_id;
        return $this;
    }

    public function getTypeCategoryId()
    {
        return $this->type_category_id;
    }
    public function setTypeCategoryId($category_id)
    {
        $this->type_category_id = $category_id;
        return $this;
    }

    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Hent innslag i denne forestillingen.
     *
     * @return Samling
     **/
    public function getInnslag()
    {
        if (null == $this->innslag) {
            $this->innslag = new Samling($this->getContext());
        }
        return $this->innslag;
    }

    /**
     * Sett ID
     *
     * @param integer id 
     *
     * @return $this
     **/
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * hent ID
     * @return integer $id
     **/
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sett navn på innslag
     *
     * @param string $navn
     * @return $this
     **/
    public function setNavn($navn)
    {
        $this->navn = $navn;
        return $this;
    }
    /**
     * Hent navn på innslag
     *
     * @return string $navn
     **/
    public function getNavn()
    {
        if (empty($this->navn)) {
            return 'Forestilling uten navn';
        }
        return $this->navn;
    }

    /**
     * Sett navn på sted for hendelsen
     *
     * @param string $sted
     * @return $this
     **/
    public function setSted($sted)
    {
        $this->sted = $sted;
        return $this;
    }
    /**
     * Hent navn på sted for hendelsen
     *
     * @return string $sted
     **/
    public function getSted()
    {
        return $this->sted;
    }

    /**
     * Sett mønstringsid (PLID)
     *
     * @param string $type
     * @return $this
     **/
    public function setMonstringId($pl_id)
    {
        $this->monstring_id = $pl_id;
        return $this;
    }
    /**
     * Hent mønstringsid (PLID)
     *
     * @param string $type
     * @return $this
     **/
    public function getMonstringId()
    {
        return $this->monstring_id;
    }
    /**
     * Hent mønstring (objektet)
     *
     * @return monstring
     **/
    public function getMonstring()
    {
        if (null == $this->monstring) {
            $this->monstring = new Arrangement($this->getMonstringId());
        }
        return $this->monstring;
    }

    /**
     * Hent direktelenke til hendelsen
     *
     * @return string url
     **/
    public function getLink()
    {
        return $this->getMonstring()->getLink()
            . 'program/?hendelse='
            . $this->getId();
    }

    /**
     * Sett start-tidspunkt
     *
     * @param unixtime $start
     * @return $this
     **/
    public function setStart($unixtime)
    {
        // Hvis gitt "unixtime" egentlig er DateTime
        if (!is_numeric($unixtime) && !is_null($unixtime) && get_class($unixtime) == 'DateTime') {
            $this->start = $unixtime->getTimestamp();
            $this->start_datetime = $unixtime;
        }
        $this->start = $unixtime;
        return $this;
    }
    /**
     * Hent start-tidspunkt
     *
     * @return DateTime $start
     **/
    public function getStart()
    {
        if (null == $this->start_datetime) {
            $this->start_datetime = new DateTime();
            $this->start_datetime->setTimestamp($this->start);
        }
        return $this->start_datetime;
    }


    /**
     * Hent nummer i rekken
     *
     * @param object innslag
     **/
    public function getNummer($searchfor)
    {
        foreach ($this->getInnslag()->getAll() as $order => $innslag) {
            if ($searchfor->getId() == $innslag->getId()) {
                return $order + 1;
            }
        }
        return false;
    }

    /**
     * Hent start-justering for oppmøte-beregning
     *
     * @return int minutter før forestillingsstart
     **/
    public function getOppmoteFor()
    {
        return $this->oppmote_for;
    }
    /**
     * Sett start-justering for oppmøte-beregning
     *
     * @param int minutter
     * @return this
     **/
    public function setOppmoteFor($minutter)
    {
        $this->oppmote_for = $minutter;
        return $this;
    }

    /**
     * Hent justering per innslag for oppmøte-beregning
     *
     * @param int minutter
     * @return int sekunder delay per innslag
     **/
    public function setOppmoteDelay($minutter)
    {
        $this->oppmote_delay = $minutter;
        return $this;
    }
    /**
     * Sett justering per innslag for oppmøte-beregning
     *
     * @return this
     **/
    public function getOppmoteDelay()
    {
        return $this->oppmote_delay;
    }

    /**
     * Hent oppmøtetidspunkt for et gitt innslag
     *
     * @return DateTime oppmøtetidspunkt
     **/
    public function getOppmoteTid($searchfor)
    {
        $oppmote = clone $this->getStart();
        $oppmote->sub(DateInterval::createFromDateString($this->getOppmoteFor() . " minutes"));
        $oppmote->add(DateInterval::createFromDateString(($this->getOppmoteDelay() * ($this->getNummer($searchfor) - 1)) . " minutes"));
        return $oppmote;
    }

    /**
     * Skal forestillingen vises i rammeprogrammet?
     *
     * @return bool
     **/
    public function erSynligRammeprogram()
    {
        return $this->synlig_i_rammeprogram;
    }
    public function getSynligRammeprogram()
    {
        return $this->erSynligRammeprogram();
    }

    /**
     * Set om forestillingen skal vises i rammeprogrammet
     *
     * @param bool synlig
     * @return $this
     **/
    public function setSynligRammeprogram($synlig)
    {
        $this->synlig_i_rammeprogram = $synlig;
        return $this;
    }


    /**
     * Skal forestillingen vises i rammeprogrammet?
     *
     * @return bool
     **/
    public function erSynligOppmotetid()
    {
        return $this->synlig_oppmotetid;
    }
    public function getSynligOppmotetid()
    {
        return $this->erSynligOppmotetid();
    }

    /**
     * Set om forestillingen skal vises i rammeprogrammet
     *
     * @param bool synlig
     * @return $this
     **/
    public function setSynligOppmotetid($synlig)
    {
        $this->synlig_oppmotetid = $synlig;
        return $this;
    }

    /**
     * Skal detaljene for forestillingen vises?
     *
     * @return bool
     **/
    public function harSynligDetaljprogram()
    {
        return $this->synlig_detaljprogram;
    }
    public function erSynligDetaljProgram()
    {
        return $this->harSynligDetaljprogram();
    }
    public function getSynligDetaljprogram()
    {
        return $this->harSynligDetaljprogram();
    }

    /**
     * Sett om rekkefølgen skal være tilgjengelig
     *
     * @param bool synlig
     * @return $this
     **/
    public function setSynligDetaljprogram($synlig)
    {
        $this->synlig_detaljprogram = $synlig;
        return $this;
    }

    /**
     * Hvor lenge varer innslagene i hendelsen?
     *
     * @return void
     */
    public function getTid() {
        return $this->getInnslag()->getTid();
    }

    public static function validateClass( $object ) {
        return is_object( $object ) &&
            in_array( 
                get_class($object),
                ['UKMNorge\Arrangement\Program\Hendelse']
            );
    }

    private function _loadFromId(Int $id ) {
        $query = new Query(
            "SELECT * FROM `smartukm_concert`
            WHERE `c_id` = '#id'",
            [
                'id' => $id
            ]
        );
        return $query->getArray();
    }
}