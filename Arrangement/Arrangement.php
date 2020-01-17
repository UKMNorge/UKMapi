<?php

namespace UKMNorge\Arrangement;

use UKMNorge\Database\SQL\Query;
use Exception;

require_once 'UKM/sql.class.php';
require_once('UKM/Autoloader.php');

use DateTime, DatePeriod, DateInterval;
use kontaktpersoner;
use statistikk;
use UKMNorge\Arrangement\Kontaktperson\Samling as KontaktpersonSamling;
use UKMNorge\Arrangement\Program\Hendelser;
use UKMNorge\Arrangement\Skjema\Skjema;
use UKMNorge\Google\StaticMap;
use UKMNorge\Arrangement\Videresending\Videresending;
use UKMNorge\Innslag\Context\Context;
use UKMNorge\Meta\Value as MetaValue;
use UKMNorge\Meta\Collection as MetaCollection;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Geografi\Fylke;
use UKMNorge\Geografi\Fylker;
use UKMNorge\Geografi\Kommuner;
use UKMNorge\Innslag\Samling;
use UKMNorge\Innslag\Typer\Typer;
use UKMNorge\Log\Samling as LogSamling;
use UKMNorge\Nettverk\Proxy\Kontaktperson as AdminKontaktProxy;

require_once 'UKM/statistikk.class.php';
require_once 'UKM/monstring_tidligere.class.php';

/**
 * Arrangement
 * All informasjon knyttet til arrangementet.
 * Sentral klasse, da denne henter inn en enorm mengde underobjekter ved behov
 * 
 * @namespace UKMNorge\Arrangement
 */
class Arrangement
{
    var $id = null;
    var $type = null;
    var $navn = null;
    var $sted = null;
    var $googleMap = null;
    var $googleMapData = null;
    var $start = null;
    var $start_datetime = null;
    var $stop = null;
    var $stop_datetime = null;
    var $frist_1 = null;
    var $frist_1_datetime = null;
    var $frist_2 = null;
    var $frist_2_datetime = null;
    var $program = null;
    var $kommuner_id = null;
    var $kommuner = null;
    var $fylke = null;
    var $fylke_id = null;
    var $sesong = null;
    var $innslag = null;
    var $path = null;
    var $har_skjema = false;
    var $skjema = null;
    var $kontaktpersoner = null;
    var $pamelding = null;
    var $registrert = null;
    var $eier_fylke = null;
    var $eier_fylke_id = null;
    var $eier_kommune = null;
    var $eier_kommune_id = null;
    var $innslagTyper = null;
    var $meta = null;
    var $har_videresending = null;
    var $synlig = true;

    var $uregistrerte = null;
    var $publikum = null;

    var $attributes = null;
    var $fylkesmonstringer = null;

    var $dager = null;
    var $netter = null;

    var $videresending = null;
    var $log = null;
    var $deleted = false;
    var $subtype = null;

    /**
     * getLoadQry
     * Brukes for å få standardiserte databaserader inn for 
     * generering via _load_by_row
     *
     * WHERE-selector og evt joins må legges på manuelt
     **/
    static function getLoadQry()
    {
        // Endret til Select in select fordi den forrige
        // kunne returnere tom rad for fylker
        return "SELECT `place`.*,
        (
            SELECT GROUP_CONCAT(`smartukm_rel_pl_k`.`k_id`)
            FROM `smartukm_rel_pl_k`
            WHERE `smartukm_rel_pl_k`.`pl_id` = `place`.`pl_id`
        ) AS `k_ids`
        FROM `smartukm_place` AS `place` 
        ";

        /* PRE 2019 */
        return "SELECT `place`.*,
					GROUP_CONCAT(`kommuner`.`k_id`) AS `k_ids`
				FROM `smartukm_place` AS `place`
				LEFT JOIN `smartukm_rel_pl_k` AS `kommuner`
					ON (`kommuner`.`pl_id` = `place`.`pl_id`)
				";
    }

    public function __construct($id_or_row)
    {

        if (is_numeric($id_or_row)) {
            $this->_load_by_id($id_or_row);
        } elseif (is_array($id_or_row)) {
            $this->_load_by_row($id_or_row);
        } else {
            throw new Exception('MONSTRING_V2: Oppretting av objekt krever numerisk id eller databaserad');
        }

        $this->attributes = array();
    }

    private function _load_by_id($id)
    {
        $qry = new Query(
            self::getLoadQry() . "WHERE `place`.`pl_id` = '#plid'",
            array('plid' => $id)
        );
        $res = $qry->run('array');

        $this->_load_by_row($res);
    }

    private function _load_by_row($row)
    {
        if (!is_array($row)) {
            throw new Exception('MONSTRING_V2: _load_by_row krever dataarray!');
        }

        if ($row['pl_type'] == 'ukjent') {
            throw new Exception(
                'Beklager, kan ikke hente mønstring ' . $row['pl_id'] .
                    ' da mønstringstypen er ukjent',
                101001
            );
        }
        // Beregn type
        $this->setType($row['pl_type']);


        $this->setFylke((Int)$row['pl_owner_fylke']);
        $this->setEierFylke((Int)$row['pl_owner_fylke']);
        $this->setEierKommune((Int)$row['pl_owner_kommune']);

        // Legg til kommuner
        if ($this->getType() == 'kommune') {
            if (null == $row['k_ids']) {
                $this->setKommuner(array());
            } else {
                $this->setKommuner(explode(',', $row['k_ids']));
            }
        }

        $this->setId($row['pl_id']);
        $this->setNavn($row['pl_name']);
        $this->setRegistrert($row['pl_registered'] == 'true');
        $this->setStart(new DateTime($row['pl_start']));
        $this->setStop(new DateTime($row['pl_stop']));
        $this->setFrist1(new DateTime($row['pl_deadline']));
        $this->setFrist2(new DateTime($row['pl_deadline2']));
        $this->setSesong(intval($row['season']));
        $this->setSted($row['pl_place']);
        $this->setGoogleMapData($row['pl_location']);
        $this->setPublikum($row['pl_public']);
        $this->setUregistrerte($row['pl_missing']);
        $this->setPamelding($row['pl_pamelding']);
        $this->setHarVideresending($row['pl_videresending'] == 'true');
        $this->har_skjema = $row['pl_has_form'] == 'true';
        $this->synlig = $row['pl_visible'] == 'true';
        $this->deleted = $row['pl_deleted'] == 'true';
        $this->subtype = $row['pl_subtype'];

        // SET PATH TO BLOG
        if (isset($row['pl_link']) || (isset($row['pl_link']) && empty($row['pl_link']))) {
            $this->setPath($row['pl_link']);
        }
        // Backwards compat
        else {
            if ('fylke' == $this->getType()) {
                $this->setPath($this->getFylke()->getLink());
            } elseif ('land' == $this->getType()) {
                $this->setPath('festivalen');
            } else {
                $this->setPath('pl' . $this->getId());
            }
        }
    }


    /**
     * Sett attributt
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
     * Hent attributt
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
     * Sett ID
     *
     * @param integer id 
     *
     * @return $this
     **/
    public function setId($id)
    {
        $this->id = (int) $id;
        return $this;
    }
    /**
     * hent ID
     * @return Int $id
     **/
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sett type
     *
     * @param String $type
     *
     * @return $this;
     **/
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    /**
     * Hent type
     *
     * @return String $type
     **/
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sett path
     *
     * @param string $path
     *
     * @return $this;
     **/
    public function setPath($path)
    {
        $this->path = rtrim(trim($path, '/'), '/');
        return $this;
    }
    /**
     * Hent relativ path for mønstringen
     *
     * @return string $path
     **/
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sett navn
     *
     * @param string $navn
     *
     * @return $this
     **/
    public function setNavn($navn)
    {
        $this->navn = $navn;
        return $this;
    }
    /**
     * hent navn
     * @return string $navn
     **/
    public function getNavn()
    {
        return $this->navn;
    }

    /**
     * Sett sted
     *
     * @param string $sted
     *
     * @return $this
     **/
    public function setSted($sted)
    {
        $this->sted = $sted;
        return $this;
    }
    /**
     * hent sted
     * @return string $sted
     **/
    public function getSted()
    {
        return $this->sted;
    }

    /**
     * Hent antall uregistrerte deltakere
     *
     * @return int uregistrerte
     **/
    public function getUregistrerte()
    {
        return $this->uregistrerte;
    }


    /**
     * Sett antall uregistrerte deltakere
     *
     * @param int antall uregistrerte deltakere
     * @return $this
     **/
    public function setUregistrerte($uregistrerte)
    {
        $this->uregistrerte = $uregistrerte;
        return $this;
    }

    /**
     * Hent antall publikummere
     *
     * @return int antall_publikum
     **/
    public function getPublikum()
    {
        return $this->publikum;
    }
    /**
     * Sett antall publikummere
     *
     * @param int antall publikummere
     * @return $this
     **/
    public function setPublikum($publikum)
    {
        $this->publikum = $publikum;
        return $this;
    }

    /**
     * Sett start-tidspunkt
     *
     * @param DateTime $start
     * @return $this
     **/
    public function setStart($time)
    {
        if (!is_numeric($time) && get_class($time) == 'DateTime') {
            $this->start_datetime = $time;
            $this->start = $time->getTimestamp();
        } else {
            $this->start = $time;
            $this->start_datetime = null;
        }
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
     * Sett stopp-tidspunkt
     *
     * @param DateTime $stop
     * @return $this
     **/
    public function setStop($time)
    {
        if (!is_numeric($time) && get_class($time) == 'DateTime') {
            $this->stop_datetime = $time;
            $this->stop = $time->getTimestamp();
        } else {
            $this->stop = $time;
            $this->stop_datetime = null;
        }
        return $this;
    }
    /**
     * Hent stopp-tidspunkt
     *
     * @return DateTime $stop
     **/
    public function getStop()
    {
        if (null == $this->stop_datetime) {
            $this->stop_datetime = new DateTime();
            $this->stop_datetime->setTimestamp($this->stop);
        }
        return $this->stop_datetime;
    }

    /**
     * Sett frist 1-tidspunkt
     *
     * @param DateTime $frist1
     * @return $this
     **/
    public function setFrist1($time)
    {
        if (!is_numeric($time) && get_class($time) == 'DateTime') {
            $this->frist_1_datetime = $time;
            $this->frist_1 = $time->getTimestamp();
        } else {
            $this->frist_1 = $time;
            $this->frist_1_datetime = 0;
        }
        return $this;
    }
    /**
     * Sett frist 2-tidspunkt
     *
     * @param DateTime $frist2
     * @return $this
     **/
    public function setFrist2($time)
    {
        if (!is_numeric($time) && get_class($time) == 'DateTime') {
            $this->frist_2_datetime = $time;
            $this->frist_2 = $time->getTimestamp();
        } else {
            $this->frist_2 = $time;
            $this->frist_2_datetime = null;
        }
        return $this;
    }
    /**
     * Hent enten frist 1 eller frist 2
     *
     * @param Int $frist
     * @return DateTime $frist
     * @throws Exception ugyldig frist
     */
    public function getFrist(Int $frist)
    {
        if( $frist == 1 ) {
            return $this->getFrist1();
        }
        if( $frist == 2 ) {
            return $this->getFrist2();
        }
        throw new Exception(
            'Kan ikke hente frist '. $frist,
            101002
        );
    }
    /**
     * Hent frist 1-tidspunkt
     *
     * @return DateTime $frist1
     **/
    public function getFrist1()
    {
        if (null == $this->frist_1_datetime) {
            $this->frist_1_datetime = new DateTime();
            $this->frist_1_datetime->setTimestamp($this->frist_1);
        }
        return $this->frist_1_datetime;
    }
    /**
     * Hent frist 2-tidspunkt
     *
     * @return DateTime $frist2
     **/
    public function getFrist2()
    {
        if (null == $this->frist_2_datetime) {
            $this->frist_2_datetime = new DateTime();
            $this->frist_2_datetime->setTimestamp($this->frist_2);
        }
        return $this->frist_2_datetime;
    }


    /**
     * Er dette en singelmønstring (altså ikke fellesmønstring
     *
     * return bool
     **/
    public function erSingelmonstring()
    {
        return 1 == sizeof($this->kommuner_id);
    }
    /**
     * Er dette en fellesmønstring 
     *
     **/
    public function erFellesmonstring()
    {
        if ($this->getType() != 'kommune') {
            return false;
        }
        return 1 < sizeof($this->kommuner_id);
    }

    /**
     * getAntallKommuner
     * Hent ut antall kommuner mønstringen har uten å laste inn objekter
     * 
     * @return integer
     **/
    public function getAntallKommuner()
    {
        if ($this->getType() !== 'kommune') {
            throw new Exception('MONSTRING_V2: getAntallKommuner kan kun kjøres på lokalmønstringer!');
        }
        return sizeof($this->kommuner_id);
    }

    /**
     * harKommune
     * Sjekker om en mønstring har en gitt kommune uten å laste inn objekter
     *
     * @param integer / kommune-object
     * @return bool
     **/
    public function harKommune($kommune)
    {
        if ($this->kommuner_id == null) {
            return false;
        }

        if (is_numeric($kommune)) {
            $kommuneId = $kommune;
        } else {
            $kommuneId = $kommune->getId();
        }
        return in_array(
            $kommuneId,
            $this->kommuner_id == null ? [] : $this->kommuner_id // For å ikke forvirre intelliphens 
        );
    }
    /**
     * Sett kommuner
     *
     * @param array $kommuner_id
     * @return $this
     **/
    public function setKommuner($kommuner_id)
    {
        $this->kommuner_id = $kommuner_id;
        return $this;
    }

    /**
     * Hent kommune
     *
     * @return object $kommune
     **/
    public function getKommune()
    {
        if (!$this->erSingelmonstring()) {
            throw new Exception('MONSTRING_V2: Kan ikke bruke getKommune på mønstringer med flere kommuner');
        }
        // Quickfix 22.09.2016
        return $this->getKommuner()->first();

        if (null == $this->kommune) {
            $this->kommune = new Kommune($this->kommune_id);
        }
        return $this->kommune;
    }

    /**
     * Hent alle kommuner for en mønstring
     *
     * @return Kommuner
     **/
    public function getKommuner()
    {
        require_once('UKM/kommuner.collection.php');

        if (null == $this->kommuner) {
            if ('kommune' == $this->getType()) {
                $this->kommuner = new Kommuner();
                foreach ($this->kommuner_id as $id) {
                    $this->kommuner->add(new Kommune($id));
                }
            } elseif ('fylke' == $this->getType()) {
                $this->kommuner = $this->getFylke()->getKommuner();
            }
        }
        return $this->kommuner;
    }

    /**
     * Sett fylkeID
     *
     * @param Int $fylke_id
     * @return $this
     * 
     **/
    public function setFylke(Int $fylke_id)
    {
        $this->fylke_id = $fylke_id;
        return $this;
    }

    /**
     * Ønskjer arrangementet å bruke skjemaet sitt?
     *
     * @return Bool $har_skjema
     **/
    public function harSkjema()
    {
        return $this->har_skjema;
    }

    /**
     * Sett om arrangementet ønsker å bruke skjemaet sitt
     *
     * @param Bool $skjema_i_do_want_it
     * @return $this
     */
    public function setHarSkjema(Bool $skjema_i_do_want_it)
    {
        $this->har_skjema = $skjema_i_do_want_it;
        return $this;
    }

    /**
     * Hent skjema
     * OBS: du kan få et skjema, selv om arrangementet ikke ønsker
     * å bruke det!
     *
     * @return skjema $skjema
     **/
    public function getSkjema()
    {
        if ($this->getType() == 'land') {
            throw new Exception('Videresendingsskjema ikke støttet for UKM-festivalen');
        }
        if ($this->skjema == null) {
            try {
                $this->skjema = Skjema::loadFromArrangement($this->getId());
            } catch (Exception $e) {
                // Betyr at arrangementet ikke har skjeam
                if ($e->getCode() == 151001) {
                    return false;
                }
                throw $e;
            }
        }
        return $this->skjema;
    }

    /**
     * Hent ut fylkesmønstringene lokalmønstringen kan sende videre til
     **/
    public function getFylkesmonstringer()
    {
        throw new Exception('DEVELOPER ALERT: getFylkesmonstringer() er ikke implementert. Kontakt support@ukm.no');
        if ($this->getType() !== 'kommune') {
            throw new Exception('MONSTRING_V2: Fylkesmønstringer kan ikke videresende til fylkesmønstringer');
        }
        require_once('UKM/monstringer.collection.php');
        if (null === $this->fylkesmonstringer) {
            $this->fylkesmonstringer = [];
            foreach ($this->getKommuner() as $kommune) {
                if (!isset($this->fylkesmonstringer[$kommune->getFylke()->getId()])) {
                    $this->fylkesmonstringer[$kommune->getFylke()->getId()] = monstringer_v2::fylke($kommune->getFylke(), $this->getSesong());
                }
            }
        }
        return $this->fylkesmonstringer;
    }

    /**
     * Sett sesong
     *
     * @param int $seson
     * @return $this
     **/
    public function setSesong( Int $sesong)
    {
        $this->sesong = $sesong;
        return $this;
    }
    /**
     * Hent sesong
     *
     * @return int $sesong
     **/
    public function getSesong()
    {
        return $this->sesong;
    }

    /**
     * Hent fylke
     *
     * @return fylke
     **/
    public function getFylke()
    {
        if (null == $this->fylke) {
            if (null == $this->fylke_id && 'kommune' == $this->getType()) {
                $first_kommune = $this->getKommuner()->first();
                if (null == $first_kommune || !is_object($first_kommune)) {
                    throw new Exception('Beklager, klarte ikke å finne en kommune som tilhører denne mønstringen');
                }
                $this->setFylke((Int)$first_kommune->getFylke()->getId());
            }
            $this->fylke = Fylker::getById((Int)$this->fylke_id);
        }
        return $this->fylke;
    }

    /**
     * Etter oppretting av hendelser, kan det være hensiktsmessig 
     * å nullstille Hendelser-collection
     *
     * @return void
     */
    public function resetProgram() {
        $this->program = null;
    }

    /**
     * Hent program for gitt mønstring
     *
     * @return Hendelser $program
     *
     **/
    public function getProgram()
    {
        if (null !== $this->program) {
            return $this->program;
        }
        $this->program = new Hendelser($this->getContext());
        return $this->program;
    }

    /**
     * Hent innslag påmeldt mønstringen
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
     * Nullstill innslag-collection
     */
    public function reloadInnslag()
    {
        $this->innslag = null;
    }

    /**
     * Hent lenke for mønstringen
     *
     * @return string url
     **/
    public function getLink()
    {
        return 'https://' . UKM_HOSTNAME . '/' . $this->getPath() . '/';
    }

    /**
     * Hent hvilke innslagstyper som kan være påmeldt denne mønstringen
     *
     * @return Typer innslagstyper 
     **/
    public function getInnslagTyper($inkluder_ressurs = false)
    {
        if (null == $this->innslagTyper) {
            $this->innslagTyper = new Typer();

            $sql = new Query(
                "SELECT `type_id`
                FROM `ukm_rel_arrangement_innslag_type`
                WHERE `pl_id` = '#pl_id'",
                [
                    'pl_id' => $this->getId()
                ]
            );
            $res = $sql->run();
            // Arrangementet bruker den nye beregningen for tillatte typer (2020)
            if( Query::numRows( $res ) > 0 ) {
                while ($r = Query::fetch($res)) {
                    $this->innslagTyper->add( Typer::getByKey( $r['type_id']));
                }
            }
            // Arrangementet bruker den gamle beregningen for tillatte typer (pre2020)
            else {
                $this->_loadInnslagTyperPre2020();
            }

            if( 0 == $this->innslagTyper->getAntall() ) {
                foreach( Typer::getStandardTyper() as $type ) {
                    $this->innslagTyper->add($type);
                }
            }
        }

        if ($inkluder_ressurs && !$this->innslagTyper->har(Typer::getByKey('ressurs'))) {
            $this->innslagTyper->add(Typer::getByName('ressurs'));
        }
        return $this->innslagTyper;
    }

    /**
     * Last inn tillatte innslagTyper etter den gamle
     * metoden (pre2020). Dette sikrer bakoverkompatibilitet, 
     * samtidig som vi trygt kan implementere den nye metoden midt i sesong
     *
     * @return void
     */
    private function _loadInnslagTyperPre2020() {
        $sql = new Query(
            "SELECT `bt_id`
                        FROM `smartukm_rel_pl_bt`
                        WHERE `pl_id` = '#pl_id'
                        ORDER BY `bt_id` ASC",
            array('pl_id' => $this->getId())
        );
        $res = $sql->run();
        $foundTypeOne = false;
        while ($r = Query::fetch($res)) {
            if (1 == $r['bt_id']) {
                $foundTypeOne = true;
                foreach (Typer::getAllScene() as $type) {
                    $this->innslagTyper->add($type);
                }
            } else {
                if (9 == $r['bt_id']) {
                    $r['bt_id'] = 8;
                }
                if (!$this->innslagTyper->find($r['bt_id'])) {
                    $this->innslagTyper->addById((Int)$r['bt_id']);
                }
            }
        }
        // Alltid legg til scene
        if (!$foundTypeOne) {
            foreach (Typer::getAllScene() as $type) {
                $this->innslagTyper->add($type);
            }
        }
        // Alltid legg til utstilling
        if (!$this->innslagTyper->har(Typer::getById(3))) {
            $this->innslagTyper->add(Typer::getByName('utstilling'));
        }
        // Alltid legg til film
        if (!$this->innslagTyper->har(Typer::getById(2))) {
            $this->innslagTyper->add(Typer::getByName('video'));
        }
    }

    /**
     * Etter lagring er det hensiktsmessig å nullstille
     * innslagTyperCollection, i tilfelle brukeren har fjernet
     * alle typer påmelding (og vi da skal defaulte til standard-utvalg)
     *
     * @return void
     */
    public function resetInnslagTyper() {
        $this->innslagTyper = null;
    }

    /**
     * getKontaktpersoner
     * Henter alle kontaktpersoner som collection
     *
     * @return KontaktpersonSamling $kontaktpersoner
     **/
    public function getKontaktpersoner()
    {
        if (null == $this->kontaktpersoner) {
            $this->_loadKontaktpersoner();
        }
        return $this->kontaktpersoner;
    }

    private function _loadKontaktpersoner()
    {
        $this->kontaktpersoner = new KontaktpersonSamling($this->getId());
        return $this;
    }

    /**
     * Hent kontaktpersoner, eller administratorer for området
     * 
     * Hvis arrangementet har kontaktpersoner returneres disse,
     * Hvis ikke, og arrangementets hovedeier har kontaktpersoner, returneres disse
     * Hvis ikke, og noen av arrangementets kommuner (forutsatt kommune-arrangement) har kontaktpersoner, returneres disse
     * Hvis ikke, og hovedeiers fylke har kontaktpersoner, returneres disse
     * Hvis ikke, da får du en tom samling da. Out-of-luck-exception.
     *
     * @return KontaktpersonSamling
     */
    public function getKontaktpersonerEllerAdministratorer() {        
        // Hvis arrangementet har kontaktpersoner - returner de
        if( $this->getKontaktpersoner()->getAntall() > 0 ) {
            return $this->getKontaktpersoner();
        }

        // Lag kopi av kontaktperson-samlingen
        $samling = $this->getKontaktpersoner();

        // Hent områdets (arrangementets hovedeier) administratorer
        if( $this->getEierOmrade()->getAdministratorer()->getAntall() > 0 ) {
            foreach( $this->getEierOmrade()->getAdministratorer()->getAll() as $admin ) {
                $samling->add( new AdminKontaktProxy( $admin ) );
            }
            return $samling;
        }

        // Hent alle kommuner i arrangementet, og finn eierområdenes administratorer
        if( $this->getType() == 'kommune' ) {
            foreach( $this->getKommuner()->getAll() as $kommune ) {
                $omrade = Omrade::getByKommune( $kommune->getId() );
                foreach( $omrade->getAdministratorer()->getAll() as $admin ) {
                    $samling->add( new AdminKontaktProxy( $admin ) );
                }
            }
        }

        // Hvis det er en kommune uten admins, hent fylkets kontaktpersoner
        if( $this->getEierOmrade()->getType() == 'kommune' ) {
            $omrade = Omrade::getByFylke( $this->getEierOmrade()->getFylke()->getId() );
            foreach( $omrade->getAdministratorer()->getAll() as $admin ) {
                $samling->add( new AdminKontaktProxy( $admin ) );
            }
            return $samling;
        }

        return $samling;
    }

    /**
     * getStatistikk
     * Hent et statistikkobjekt relatert til denne mønstringen
     *
     * @return statistikk
     **/
    public function getStatistikk()
    {
        require_once('UKM/statistikk.class.php');
        $this->statistikk = new statistikk();

        if ('kommune' == $this->getType()) {
            $this->statistikk->setKommune($this->getKommuner()->getIdArray());
        } elseif ('fylke' == $this->getType()) {
            $this->statistikk->setFylke($this->getFylke()->getId());
        } else {
            $this->statistikk->setLand();
        }
        return $this->statistikk;
    }

    /**
     * Er mønstringen registrert?
     *
     * @return Bool $registrert
     */
    public function erRegistrert()
    {
        return $this->registrert;
    }

    public function erStartet()
    {
        return time() > $this->getStart()->getTimestamp();
    }

    public function erAktiv()
    {
        return $this->erStartet() && !$this->erFerdig();
    }
    public function erFerdig()
    {
        return time() > $this->getStop()->getTimestamp();
    }

    public function erPameldingApen($frist = 'begge')
    {
        if ($frist == 1 || $frist == 'frist_1') {
            return time() < $this->getFrist1()->getTimestamp();
        }
        if ($frist == 2 || $frist == 'frist_2') {
            return time() < $this->getFrist2()->getTimestamp();
        }
        $res = time() < $this->getFrist1()->getTimestamp() || time() < $this->getFrist2()->getTimestamp();
        return $res;
    }

    public function erVideresendingApen()
    {
        return time() < $this->getFrist1()->getTimestamp() && time() > $this->getFrist2()->getTimestamp();
    }

    /**
     * erOslo
     * Returnerer om fylket er Oslo.
     * Brukes i hovedsak til å velge mellom kommune eller bydel i GUI
     *
     * @return bool
     **/
    public function erOslo()
    {
        return $this->getFylke()->getId() == 3;
    }

    /**
     * Hvor mange dager varer mønstringen?
     *
     * @return int $dager
     **/
    public function getDager()
    {
        if (null == $this->dager) {
            $period = new DatePeriod(
                $this->getStart(),
                new DateInterval('P1D'),
                $this->getStop()
            );
            $this->dager = iterator_to_array($period);
        }
        return $this->dager;
    }

    public function getAntallDager()
    {
        return sizeof($this->getDager());
    }

    /**
     * Hvilke netter går mønstringen over?
     *
     * @return array $netter
     **/
    public function getNetter()
    {
        if (!isset($this->netter)) {
            $netter = $this->getDager();
            $this->netter = $netter;
        }
        return $this->netter;
    }


    /**
     * eksisterer
     * 
     * @return bool
     **/
    public function eksisterer()
    {
        return !is_null($this->id);
    }

    protected function _resetKommuner()
    {
        $this->kommuner = null;
    }

    public function getContext()
    {
        if ('land' == $this->getType()) {
            $context = Context::createMonstring(
                $this->getId(),                         // Mønstring id
                $this->getType(),                       // Møntring type
                $this->getSesong(),                     // Mønstring sesong
                null,                                   // Mønstring fylke ID
                null                                    // Mønstring kommune ID array
            );
        } else {
            $context = Context::createMonstring(
                $this->getId(),                         // Mønstring id
                $this->getType(),                       // Møntring type
                $this->getSesong(),                     // Mønstring sesong
                $this->getFylke()->getId(),             // Mønstring fylke ID
                ($this->getType() == 'kommune' ?        // Mønstring kommune ID array
                    $this->getKommuner()->getIdArray() :
                    null)
            );
        }
        return $context;
    }

    /**
     * Reset personer collection (kun på objektbasis)
     *
     **/
    public function resetInnslagCollection()
    {
        $this->innslag = null;
        return $this;
    }

    /**
     * Tar mønstringen i mot påmelding fra deltakere
     * Skiller ikke på åpen eller betinget påmelding
     * @return Bool
     */
    public function harPamelding()
    {
        return in_array(
            $this->getPamelding(),
            ['apen', 'betinget']
        );
    }

    /**
     * Hvilken type påmelding har mønstringen
     * 
     * @return Bool
     */
    public function getPamelding()
    {
        return $this->pamelding;
    }

    /**
     * Si om mønstringen skal ta i mot påmelding fra deltakere
     *
     * @param String $pamelding
     * @return  self
     */
    public function setPamelding($pamelding)
    {
        $this->pamelding = $pamelding;

        return $this;
    }


    /**
     * Tar mønstringen i mot videresendte?
     *
     * @return bool
     */
    public function harVideresending()
    {
        return $this->har_videresending;
    }

    /**
     * Angi om mønstringen tar i mot videresendte
     *
     * @param Bool $bool
     * @return Arrangement $this
     */
    public function setHarVideresending(Bool $bool)
    {
        $this->har_videresending = $bool;
        return $this;
    }

    /**
     * Get the value of registrert
     */
    public function getRegistrert()
    {
        return $this->registrert;
    }
    
    /**
     * Sett om mønstringen er registrert eller ikke
     *
     * @param Bool $registrert
     * @return self
     */
    public function setRegistrert( Bool $registrert)
    {
        $this->registrert = $registrert;

        return $this;
    }

    /**
     * Hvilket fylke tilhører eieren av arrangementet
     *
     * @return Fylke
     */
    public function getEierFylke()
    {
        if (null == $this->eier_fylke) {
            $this->eier_fylke = Fylker::getById((Int)$this->eier_fylke_id);
        }
        return $this->eier_fylke;
    }

    /**
     * Sett hvilket fylke eieren av arrangementet tilhører
     *
     * @param (Int|Fylke) $fylke
     * @return  self
     */
    public function setEierFylke($fylke)
    {
        if (Fylke::validateClass($fylke)) {
            $this->eier_fylke = $fylke;
            $this->eier_fylke_id = $fylke->getId();
        } else {
            $this->eier_fylke = null;
            $this->eier_fylke_id = (Int) $fylke;
        }

        return $this;
    }


    /**
     * Hvilken kommune tilhører eieren av arrangementet
     * 
     * @return Kommune
     */
    public function getEierKommune()
    {
        if (0 == $this->eier_kommune_id) {
            return false;
        }

        if (null == $this->eier_kommune) {
            $this->eier_kommune = new Kommune($this->eier_kommune_id);
        }
        return $this->eier_kommune;
    }

    /**
     * Sett hvilken kommune eieren av arrangementet tilhører
     *
     * @param (Int|Kommune) $kommune
     * @return  self
     */
    public function setEierKommune($kommune)
    {
        if (Kommune::validateClass($kommune)) {
            $this->eier_kommune = $kommune;
            $this->eier_kommune_id = $kommune->getId();
        } else {
            $this->eier_kommune = null;
            $this->eier_kommune_id = $kommune;
        }

        return $this;
    }

    /**
     * Hvilken type eier har dette arrangementet
     *
     * @return String (kommune|fylke)
     */
    public function getEierType()
    {
        if ($this->getEierKommune()) {
            return 'kommune';
        }
        return 'fylke';
    }

    /**
     * Hent eier for dette arrangementet
     *
     * @return kommune|fylke
     */
    public function getEier()
    {
        if ($this->getEierType() == 'kommune') {
            return $this->getEierKommune();
        }
        return $this->getEierFylke();
    }

    /**
     * Hent eier for arrangementet
     *
     * @return Eier
     */
    public function getEierObjekt()
    {
        return new Eier($this->getEierType(), $this->getEier()->getId());
    }

    /**
     * Hent eier-området for arrangementet
     *
     * @return Omrade
     */
    public function getEierOmrade()
    {
        if ($this->getEierType() == 'kommune') {
            return Omrade::getByKommune($this->getEierKommune()->getId());
        }
        return Omrade::getByFylke($this->getEierFylke()->getId());
    }

    /**
     * Har arrangementet et kart?
     *
     * @return Bool
     */
    public function harKart() {
        return $this->getKart()->hasMap();
    }
    /**
     * Hent inn kart-objektet
     *
     * @see getGoogleMap()
     *
     * @return StaticMap
     */
    public function getKart() {
        return $this->getGoogleMap();
    }

    /**
     * Get the value of googleMap
     */
    public function getGoogleMap()
    {
        if (null == $this->googleMap) {
            if( defined('GOOGLE_API_KEY') ) {
                StaticMap::setApiKey(GOOGLE_API_KEY);
            }
            $this->googleMap = StaticMap::fromJSON(json_decode($this->getGoogleMapData()));
        }
        return $this->googleMap;
    }

    /**
     * Set the value of googleMapData
     *
     * @return  self
     */
    public function setGoogleMapData($jsonData)
    {
        $this->googleMapData = $jsonData;

        return $this;
    }

    /**
     * Get the value of googleMapData
     *
     * @return Json $googleMapData
     */
    public function getGoogleMapData()
    {
        return $this->googleMapData;
    }

    /**
     * Er dette en typisk mønstring?
     * 
     * Mønstringer kan ta i mot ulike typer påmeldinger
     *
     * @return Bool
     */
    public function erMonstring() {
        return $this->subtype == 'monstring';
    }

    /**
     * Er dette et enkelt arrangement (workshop)
     * 
     * Enkle arrangement kan kun ta i mot påmeldinger for enkeltpersoner
     *
     * @return void
     */
    public function erArrangement() {
        return $this->subtype == 'arrangement';
    }

    /**
     * Hent informasjon om videresending til og fra denne mønstringen
     * Alle mønstringer kan ta i mot videresendinger
     * 
     * @return Videresending
     */
    public function getVideresending()
    {
        if (null == $this->videresending) {
            $this->videresending = new Videresending((int) $this->getId());
        }
        return $this->videresending;
    }

    /**
     * Hent metadata
     * 
     * @param String $key
     * @return MetaValue $metadata
     */
    public function getMeta($key)
    {
        if (null == $this->meta) {
            $this->meta = MetaCollection::createByParentInfo(
                'arrangement',
                $this->getId()
            );
        }
        return $this->meta->get($key);
    }

    /**
     * Hent verdi av metadata
     *
     * @param String $key
     * @return Any $metadata
     */
    public function getMetaValue($key)
    {
        return $this->getMeta($key)->getValue();
    }


    /**
     * Hent informasjonstekst som skal vises ved videresending til dette arrangementet
     *
     * @return String $html_text
     */
    public function getInformasjonstekst()
    {
        return $this->getMetaValue('infotekst_videresending');
    }

    /**
     * Hent pressemelding for arrangementet (hvis dette finnes)
     *
     * @return String pressemelding HTML
     */
    public function getPressemelding() {
        $pressemelding = $this->getMetaValue('pressemelding');
        if( is_string($pressemelding) && strlen($pressemelding) > 0 ) {
            return $pressemelding;
        }
        return '';
    }

    /**
     * Er arrangementet synlig?
     * @alias erSynlig
     */
    public function getSynlig()
    {
        return $this->erSynlig();
    }

    /**
     * Er arrangementet synlig?
     *
     * @return Bool $synlig
     */
    public function erSynlig()
    {
        return $this->synlig;
    }

    /**
     * Sett om arrangementet skal være synlig
     *
     * @param Bool $synlig
     * @return $this
     */
    public function setSynlig(Bool $synlig)
    {
        $this->synlig = $synlig;

        return $this;
    }


    public static function validateClass($object)
    {
        return is_object($object) &&
            in_array(
                get_class($object),
                ['UKMNorge\Arrangement\Arrangement', 'monstring_v2']
            );
    }

    /**
     * Get the value of log
     */ 
    public function getLog()
    {
        if( null == $this->log ) {
            $this->log = new LogSamling('arrangement', $this->getId());
        }
        return $this->log;
    }

    /**
     * Er arrangementet slettet?
     */ 
    public function erSlettet() {
        return $this->deleted;
    }

    /**
     * Set the value of deleted
     *
     * @param Bool $deleted
     * @return self
     */ 
    public function setSlettet(Bool $deleted)
    {
        $this->deleted = $deleted;
        return $this;
    }

    /**
     * Henter type arrangement (bruk heller erMonstring() eller erArrangement())
     * 
     * @return String
     */ 
    public function getSubtype()
    {
        return $this->subtype;
    }

    /**
     * Set the value of subtype
     *
     * @param String $subtype
     * @return self
     */ 
    public function setSubtype(String $subtype)
    {
        $this->subtype = $subtype;

        return $this;
    }
}
