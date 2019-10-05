<?php

namespace UKMNorge\Innslag;

use Exception;
use DateTime;

use bilder;
use tv_files;
use artikler;
use playback_collection;
use program;
use nominasjon_media, nominasjon_konferansier, nominasjon_arrangor, nominasjon_placeholder;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Innslag\Advarsler\Advarsel;
use UKMNorge\Innslag\Advarsler\Advarsler;
use UKMNorge\Innslag\Context\Context;
use UKMNorge\Innslag\Mangler\Mangler;
use UKMNorge\Innslag\Personer\Person;
use UKMNorge\Innslag\Personer\Personer;
use UKMNorge\Innslag\Titler\Titler;
use UKMNorge\Samtykke\Innslag as InnslagSamtykke;


class Innslag
{
    var $context = null;

    var $id = null;
    var $navn = null;
    var $type = null;
    var $beskrivelse = null;
    var $kommune_id = null;
    var $kommune = null;
    var $fylke = null;
    var $filmer = false;
    var $program = null;
    var $kategori = null;
    var $sjanger = null;
    var $playback = null;
    var $personer_collection = null;
    var $artikler_collection = null;
    var $attributes = null;
    var $sesong = null;
    var $avmeldbar = false;
    var $advarsler = null;
    var $mangler = null;
    var $mangler_json = '';
    var $titler = null;
    var $home = null;
    var $home_id = null;

    var $delta_eier = null;

    var $erVideresendt = null;
    var $nominasjon = null;

    var $kontaktperson_id = null;
    var $kontaktperson = null;

    var $tekniske_behov = null;

    var $videresendt_til = null;

    public function __construct($bid_or_row, $select_also_if_not_completed = false)
    {
        $this->attributes = array();
        if (null == $bid_or_row || empty($bid_or_row)) {
            throw new Exception('INNSLAG_V2: Konstruktør krever b_id som numerisk verdi eller array med innslag-data. Gitt ' . var_export($bid_or_row, true));
        }
        if (is_numeric($bid_or_row)) {
            $this->_loadByBID($bid_or_row, $select_also_if_not_completed);
        } else {
            $this->_loadByRow($bid_or_row);
        }
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

    public static function getById(Int $id, Bool $also_if_incomplete = false)
    {
        $contextQry = new Query(
            "SELECT `b_home_pl`
            FROM `smartukm_band`
            WHERE `b_id` = '#b_id'",
            [
                'b_id' => $id
            ]
        );
        $homePlace = new Arrangement($contextQry->run('field'));

        $context = Context::createMonstring($homePlace->getId(), $homePlace->getType(), $homePlace->getSesong(), false, false);

        $innslag = new Innslag($id, $also_if_incomplete);
        $innslag->setContext($context);

        return $innslag;
    }


    public static function getLoadQuery()
    {
        return "SELECT `smartukm_band`.*, 
                        `td`.`td_demand`,
                        `td`.`td_konferansier`
                FROM `smartukm_band`
                LEFT JOIN `smartukm_technical` AS `td` ON (`td`.`b_id` = `smartukm_band`.`b_id`)";
    }

    /**
     * Last inn objekt fra innslagsID
     *
     * @param integer b_id 
     * @return this;
     *
     **/
    /* OBS OBS OBS: DENNE SKAL VEL IKKE BRUKES ?!?! */
    static function getLoadQry()
    {
        return "SELECT `smartukm_band`.*, 
                        `td`.`td_demand`,
                        `td`.`td_konferansier`
                ";
    }

    private function _loadByBID($b_id, $select_also_if_not_completed)
    {
        $SQL = new Query(
            self::getLoadQuery() . "
                        WHERE `smartukm_band`.`b_id` = '#bid' 
                        #select_also_if_not_completed",
            array(
                'bid' => $b_id,
                'select_also_if_not_completed' => ($select_also_if_not_completed ? '' : "AND `smartukm_band`.`b_status` = 8")
            )
        );
        $row = $SQL->run('array');

        $this->_loadByRow($row);
        return $this;
    }
    /**
     * Last inn objekt fra databaserad
     *
     * @param database_row $row
     * @return $this;
     **/
    private function _loadByRow($row)
    {
        $this->setId($row['b_id']);
        if (null == $this->getId()) {
            throw new Exception("INNSLAG_V2: Klarte ikke å laste inn innslagsdata");
        }
        $this->setNavn($row['b_name']);
        $this->setType($row['bt_id'], $row['b_kategori']);
        $this->setBeskrivelse($row['b_description']);
        $this->setKommune($row['b_kommune']);
        $this->setKategori($row['b_kategori']);
        $this->setSjanger((string) $row['b_sjanger']);
        $this->setKontaktpersonId($row['b_contact']);
        $this->_setSubscriptionTime($row['b_subscr_time']);
        $this->setStatus($row['b_status']);
        $this->setTekniskeBehov($row['td_demand']);

        $this->delta_eier = $row['b_password'];
        $this->home_id = (int) $row['b_home_pl'];
        $this->mangler_json = (string) $row['b_status_object'];

        if (isset($row['order'])) {
            $this->setAttr('order', $row['order']);
        } else {
            $this->setAttr('order', null);
        }

        $this->setSesong($row['b_season']);

        return $this;
    }

    /**
     * Hent alle bilder tilknyttet innslaget
     *
     * @return array $bilder
     **/
    public function getBilder()
    {
        require_once('UKM/bilder.class.php');
        $this->bilder = new bilder($this->getId());

        return $this->bilder;
    }

    /**
     * Hent alle filmer fra UKM-TV (tilknyttet innslaget)
     *
     * @return array UKM-TV
     **/
    public function getFilmer()
    {
        if (!is_array($this->filmer)) {
            require_once('UKM/tv.class.php');
            require_once('UKM/tv_files.class.php');

            $tv_files = new tv_files('band', $this->getId());
            while ($tv = $tv_files->fetch()) {
                $this->filmer[$tv->id] = $tv;
            }
        }
        return $this->filmer;
    }

    /**
     * Hent relaterte artikler
     *
     * @return artikkel_collection
     **/
    public function getArtikler()
    {
        if (null == $this->artikler_collection) {
            require_once('UKM/artikler.class.php');
            $this->artikler_collection = new artikler($this->getId());
        }
        return $this->artikler_collection;
    }
    private function _getNewOrOld($new, $old)
    {
        return null == $this->$new ? $this->info[$old] : $this->$new;
    }

    public function getSamtykke()
    {
        if (null == $this->samtykker) {
            $this->samtykker = new InnslagSamtykke($this);
        }
        return $this->samtykker;
    }

    public function getNominasjon($monstring)
    {
        require_once('UKM/nominasjon.class.php');
        if (null == $this->nominasjon) {

            if (!is_object($monstring)) {
                throw new Exception('INNSLAG: Mønstring må være gitt som objekt for å hente nominasjon');
            }

            switch ($this->getType()->getKey()) {
                case 'nettredaksjon':
                case 'media':
                    $classname = 'nominasjon_media';
                    break;
                case 'konferansier':
                    $classname = 'nominasjon_konferansier';
                    break;
                case 'arrangor':
                    $classname = 'nominasjon_arrangor';
                    break;
                default:
                    $classname = 'nominasjon_placeholder';
                    $key = false;
            }
            $this->nominasjon = new $classname($this->getId(), $this->getType()->getKey(), $monstring->getType());
        }

        return $this->nominasjon;
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
     * Hent ID
     * @return integer $id
     **/
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sett status
     *
     * @param integer status 
     *
     * @return $this
     **/
    public function setStatus($status)
    {
        $this->status = $status;
        return $this;
    }
    /**
     * Hent status
     * @return integer $status
     **/
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Er innslaget fullstendig påmeldt?
     * Eller er det fortsatt i prosess?
     *
     * @return Bool
     */
    public function erPameldt()
    {
        return $this->getStatus() == 8;
    }

    /**
     * Sett navn på innslag
     *
     * @param string $navn
     * @return $this
     **/
    public function setNavn($navn)
    {
        $this->navn = stripslashes($navn);
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
            return 'Innslag uten navn';
        }
        return $this->navn;
    }

    /**
     * Sett type
     * Hvilken kategori faller innslaget inn under?
     *
     * @param integer $type
     * @param string $kategori
     *
     * @return $this;
     **/
    public function setType($type, $kategori = false)
    {
        $this->type = Typer::getById($type, $kategori);
        return $this;
    }
    /**
     * Hent type
     * Hvilken kategori innslaget faller inn under
     *
     * @return Type $type
     **/
    public function getType()
    {
        return $this->type;
    }


    /**
     * Sett sesong
     *
     * @param int $seson
     * @return $this
     **/
    public function setSesong($sesong)
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
     * Sett tekniske behov
     *
     * @param string $tekniske_behov
     * @return $this
     **/
    public function setTekniskeBehov($tekniske_behov)
    {
        $this->tekniske_behov = stripslashes($tekniske_behov);
        return $this;
    }

    /**
     * Hent tekniske behov
     *
     * @return string $tekniske_behov
     **/
    public function getTekniskeBehov()
    {
        return $this->tekniske_behov;
    }

    /**
     * Sett beskrivelse av innslag
     *
     * @param beskrivelse
     * @return $this
     **/
    public function setBeskrivelse($beskrivelse)
    {
        $this->beskrivelse = stripslashes($beskrivelse);
        return $this;
    }
    /**
     * Hent beskrivelse
     *
     * @return string $beskrivelse
     **/
    public function getBeskrivelse()
    {
        return $this->beskrivelse;
    }


    /**
     * Sett kommune
     *
     * @param kommune_id
     * @return $this
     **/
    public function setKommune($kommune_id)
    {
        $this->kommune_id = $kommune_id;
        return $this;
    }
    /**
     * Hent kommune
     *
     * @return object $kommune
     **/
    public function getKommune()
    {
        if (null == $this->kommune) {
            $this->kommune = new Kommune($this->kommune_id);
        }
        return $this->kommune;
    }

    /**
     * Sett fylke
     * Skal ikke skje - sett alltid kommune!
     * 
     **/
    public function setFylke($fylke_id)
    {
        throw new Exception('INNSLAG V2: setFylke() er ikke mulig. Bruk setKommune( $kommune_id )');
    }

    /**
     * Hent fylke
     *
     * @return fylke
     **/
    public function getFylke()
    {
        if (null == $this->fylke) {
            $this->fylke = $this->getKommune()->getFylke();
        }
        return $this->fylke;
    }


    /**
     * Set subscriptionTime
     *
     * @param unixtimestamp subscriptiontime
     * @return $this;
     **/
    public function _setSubscriptionTime($unixtime)
    {
        $this->subscriptionTime = $unixtime;
        $this->_calcAvmeldbar();
        return $this;
    }

    /**
     * avmeldbar Periode - hvor lang tid har innslaget?
     * Hvor mange dager skal innslaget få for å fullføre sin påmelding?
     *
     * @return int dager
     **/
    public static function avmeldbarPeriode()
    {
        return 5;
    }

    /**
     * Skal innslaget være mulig å melde av?
     * De 5 første dagene bør innslaget få lov til å fullføre sin påmelding
     * uten at arrangører avmelder
     *
     * @param integer subscriptiontime as unixtime
     */
    private function _calcAvmeldbar()
    {
        if (time() > $this->getAvmeldbar()) {
            $this->avmeldbar = true;
        } else {
            $this->avmeldbar = false;
        }
        return $this;
    }

    /**
     * Er innslaget være mulig å melde av?
     * De 5 første dagene bør innslaget få lov til å fullføre sin påmelding
     * uten at arrangører avmelder
     *
     * @return bool
     */
    public function erAvmeldbar()
    {
        return $this->avmeldbar;
    }

    /**
     * Er innslaget være mulig å melde av?
     * De 5 første dagene bør innslaget få lov til å fullføre sin påmelding
     * uten at arrangører avmelder
     *
     * @return bool
     */
    public function getAvmeldbar()
    {
        $subscriptiontime = $this->getSubscriptionTime();
        if (is_object($subscriptiontime)) {
            return $subscriptiontime->getTimestamp() + (self::avmeldbarPeriode() * 24 * 60 * 60);
        }
        return false;
    }

    /**
     * Sett innslagets kategori
     *
     * @param string $kategori
     * @return $this;
     **/
    public function setKategori($kategori)
    {
        // Hvis scene-innslag, bruk detaljert info
        if (1 == $this->getType()->getId()) {
            $this->kategori = $this->getType()->getNavn();
        }
        $this->kategori = $kategori;
        return $this;
    }
    /**
     * Hent innslagets kategori
     *
     * @return string $kategori
     **/
    public function getKategori()
    {
        return $this->kategori;
    }

    /** 
     * Sett innslagets sjanger
     * 
     * @param string $sjanger
     * @return $this
     **/
    public function setSjanger($sjanger)
    {
        $this->sjanger = stripslashes($sjanger);
        return $this;
    }
    /**
     * Hent innslagets sjanger
     *
     * @return string $sjanger
     **/
    public function getSjanger()
    {
        return $this->sjanger;
    }

    /**
     * Hent innslagets kategori og sjanger som én streng
     * Hvis ett av feltene er tomme returneres kun det andre
     *
     * @return string $kategori ( - ) $sjanger
     *
     **/
    public function getKategoriOgSjanger()
    {
        if (!empty($this->getKategori()) && !empty($this->getSjanger())) {
            return $this->getKategori() . ' - ' . $this->getSjanger();
        }

        // En av de er tomme, returner "kun" den andre :)
        return $this->getKategori() . $this->getSjanger();
    }


    /**
     * Sett kontaktperson ID
     *
     * @param object person
     * @return $this
     **/
    public function setKontaktpersonId($person_id)
    {
        $this->kontaktperson_id = $person_id;
        return $this;
    }
    /**
     * Hent kontaktpersonId
     *
     * @return int $kontaktpersonid
     *
     **/
    public function getKontaktpersonId()
    {
        return (int) $this->kontaktperson_id;
    }
    /**
     * Sett kontaktperson 
     *
     * @param $kontaktperson
     * @return $this
     **/
    public function setKontaktperson($person)
    {
        $this->kontaktperson = $person;
        return $this;
    }

    /**
     * Hent kontaktperson
     *
     * @return object person $kontaktperson
     **/
    public function getKontaktperson()
    {
        if (null == $this->kontaktperson) {
            $person = new Person($this->getKontaktpersonId());
            $this->setKontaktperson($person);
        }
        return $this->kontaktperson;
    }

    /**
     * Hent playback
     *
     * @return playback_collection
     *
     **/
    public function getPlayback()
    {
        require_once('UKM/playback.collection.php');
        if (null == $this->playback) {
            $this->playback = new playback_collection($this->getId());
        }
        return $this->playback;
    }

    /**
     * Hent påmeldingstidspunkt
     *
     * @return DateTime tidspunkt
     **/
    public function getSubscriptionTime()
    {
        //
        // OBS OBS OBS OBS OBS
        //
        // AVVIKER FRA V1-kode
        // Pre UKMdelta var korrekt påloggingstidspunkt for tittelløse innslag
        // lagret i loggen. Sjekker kun denne loggtabellen hvis innslaget ikke har 
        // b_subscr_time
        if (empty($this->subscriptionTime)) {
            $qry = new Query(
                "SELECT `log_time` FROM `ukmno_smartukm_log`
                            WHERE `log_b_id` = '#bid'
                            AND `log_code` = '22'
                            ORDER BY `log_id` DESC",
                array('bid' => $this->getId())
            );
            $this->subscriptionTime = $qry->run('field', 'log_time');
        }

        $datetime = new DateTime();
        $datetime->setTimestamp($this->subscriptionTime);
        return $datetime;
    }

    /**
     * Hent personer i innslaget
     *
     * @return array $personer
     **/
    public function getPersoner()
    {
        if (null == $this->personer_collection) {
            $this->personer_collection = new Personer(
                $this->getId(),     // Innslag ID
                $this->getType(),     // Innslag type
                $this->getContext()    // Innslagets nåværende kontekst
            );
        }
        return $this->personer_collection;
    }

    /**
     * Hent program for dette innslaget på gitt mønstring
     *
     * INTERNALS: program bruker context som eneste input-parameter
     * da dette er en collection som også eksisterer i andre 
     * tilstander enn kun i tilknytning til innslag (som personer og titler)
     *
     * @param monstring $monstring
     * @return list program
     *
     **/
    public function getProgram()
    {
        if (null == $this->program) {
            require_once('UKM/forestillinger.collection.php');
            $context = Context::createInnslag(
                $this->getId(),                                        // Innslag ID
                $this->getType(),                                    // Innslag type (objekt)
                $this->getContext()->getMonstring()->getId(),        // Mønstring ID
                $this->getContext()->getMonstring()->getType(),        // Mønstring type
                $this->getContext()->getMonstring()->getSesong()    // Mønstring sesong
            );
            $this->program = new program($context);
        }
        return $this->program;
    }

    public function getTitler()
    {
        if (null == $this->titler) {
            $this->titler = new Titler(
                $this->getId(),     // Innslag ID
                $this->getType(),     // Innslag type
                $this->getContext()    // Innslagets nåværende kontekst
            );
        }
        return $this->titler;
    }

    /**
     * Sjekk om innslaget er videresendt fra lokalnivå.
     *
     * @return boolean
     */
    public function erVideresendt()
    {
        if (null != $this->erVideresendt) {
            return $this->erVideresendt;
        }

        $qry = new SQL(
            "SELECT COUNT(*) FROM `smartukm_rel_pl_b` WHERE `b_id` = '#b_id'",
            array('b_id' => $this->getId())
        );
        $res = $qry->run('field', 'COUNT(*)');
        if ($res > 1) {
            $this->erVideresendt = true;
            return true;
        }

        $qry = new SQL(
            "SELECT COUNT(*) FROM `smartukm_fylkestep` WHERE `b_id` = '#b_id'",
            array('b_id' => $this->getId())
        );
        $res = $qry->run('field', 'COUNT(*)');
        if ($res > 0) {
            $this->erVideresendt = true;
            return true;
        }

        $this->erVideresendt = false;
        return false;
    }

    public function erVideresendtTil($monstring)
    {
        if (is_object($monstring) && get_class($monstring) == 'monstring_v2') {
            $monstring_id = $monstring->getId();
        } elseif (is_numeric($monstring)) {
            $monstring_id = $monstring;
        } else {
            throw new Exception('erVideresendtTil krever mønstring-objekt eller numerisk id som input-parameter');
        }

        if ($monstring_id == $this->getContext()->getMonstring()->getId()) {
            throw new Exception('Feil bruk av erVideresendtTil(): kan ikke sjekke om et innslag er videresendt til mønstringen det kommer fra');
        }

        if (is_array($this->videresendt_til) && isset($this->videresendt_til[$monstring_id])) {
            return $this->videresendt_til[$monstring_id];
        }

        $qry = new SQL(
            "
            SELECT `rel`.`pl_id` 
            FROM `smartukm_rel_pl_b` AS `rel`
            LEFT JOIN `smartukm_place` AS `place` 
                ON (`place`.`pl_id` = `rel`.`pl_id`)
            WHERE `rel`.`b_id` = '#b_id'
            AND `rel`.`pl_id` = '#pl_id'
            ",
            [
                'b_id' => $this->getId(),
                'pl_id' => $monstring_id
            ]
        );
        $res = $qry->run('field', 'pl_id');
        $this->videresendt_til[$monstring_id] = $res !== null;

        return $this->videresendt_til[$monstring_id];
    }

    public function videresendesTil()
    {
        return $this->getFylke();
    }

    /**
     * Beregn advarsler for innslaget
     * Ikke det samme som mangler - dette er hint til admin
     *
     * @return Advarsler
     */
    private function _calcAdvarsler()
    {
        $advarsler = new Advarsler();

        // Har 0 personer
        if (0 == $this->getPersoner()->getAntall()) {
            $advarsler->add(Advarsel::ny('personer', 'Innslaget har ingen personer'));
        }
        // Utstilling har mer enn 3 verk
        if ('utstilling' == $this->getType()->getKey() && $this->getTitler()->getAntall() > 3) {
            $advarsler->add(Advarsel::ny('titler', 'Innslaget har mer enn 3 kunstverk'));
            // Utstilling har ingen verk
        } elseif ('utstilling' == $this->getType()->getKey() && $this->getTitler()->getAntall() == 0) {
            $advarsler->add(Advarsel::ny('titler', 'Innslaget har ingen kunstverk'));
            // Innslaget har mer enn 3 titler
        } elseif ($this->getType()->harTitler() && $this->getTitler()->getAntall() > 2) {
            $advarsler->add(Advarsel::ny('titler', 'Innslaget har mer enn 2 titler'));
            // Innslaget har ingen titler
        } elseif ($this->getType()->harTitler() && $this->getTitler()->getAntall() == 0) {
            $advarsler->add(Advarsel::ny('titler', 'Innslaget har ingen titler, og derfor ingen varighet.'));
        }
        // Innslaget har en varighet over 5 min
        if ($this->getType()->harTitler() && (5 * 60) < $this->getVarighet()->getSekunder()) {
            $advarsler->add(Advarsel::ny('titler', 'Innslaget er lengre enn 5 minutter '));
        }
        return $advarsler;
    }

    public function getVarighet()
    {
        return $this->getTitler()->getVarighet();
    }

    /**
     * Reset personer collection (kun på objektbasis)
     *
     **/
    public function resetPersonerCollection()
    {
        $this->personer_collection = null;
        return $this;
    }

    /**
     * Sett attributt
     * Sett egenskaper som for enkelhets skyld kan følge innslaget et lite stykke
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

    public static function validateClass($object)
    {
        return is_object($object) &&
            in_array(
                get_class($object),
                ['UKMNorge\Innslag\Innslag', 'innslag_v2']
            );
    }

    /**
     * Hent eier
     * Refererer til UKMDelta-bruker
     * 
     * @return String 'delta_$user_id'
     */
    public function getEier()
    {
        return $this->delta_eier;
    }

    /**
     * Hent innslagets hjemme-arrangement
     * (hvor det ble opprettet)
     * 
     * @return Arrangement
     */
    public function getHome()
    {
        if (null == $this->home) {
            $this->home = new Arrangement($this->getHomeId());
        }
        return $this->home;
    }

    /**
     * Hent ID for innslagets hjemme-arrangement
     * Hvor det ble opprettet
     * 
     * @return Int $id
     */
    public function getHomeId()
    {
        return $this->home_id;
    }

    /**
     * Hent advarsler til administrator
     * Dette er ikke samme som mangler
     *
     * @return void
     */
    public function getAdvarsler()
    {
        if (null == $this->advarsler) {
            $this->advarsler = $this->_calcAdvarsler();
        }
        return $this->advarsler;
    }

    /**
     * Hent registrerte mangler på innslaget
     * 
     * @return Mangler
     */
    public function getMangler()
    {
        if (null == $this->mangler) {
            $this->mangler = Mangler::loadFromJSON($this->mangler_json);
        }
        return $this->mangler;
    }

    /**
     * Hent registrerte mangler i JSON-format
     * 
     * @return String $json
     */
    public function getManglerJSON()
    {
        return $this->getMangler()->toJSON();
    }

    /**
     * Evaluer et innslag for å se hvilke felt som mangler
     * 
     * @return $this
     */
    public function evaluerMangler()
    {
        $this->mangler = Mangler::evaluer($this);
        $this->mangler_json = $this->mangler->toJSON();
        return $this;
    }
}
