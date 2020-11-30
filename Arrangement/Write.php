<?php

namespace UKMNorge\Arrangement;

use UKMNorge\Log\Logger;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Geografi\Fylke;
use UKMNorge\Geografi\Kommune;

use Exception;
use DateTime;
use UKMNorge\Arrangement\Kontaktperson\Kontaktperson;
use UKMNorge\Arrangement\Program\Write as WriteHendelse;
use UKMNorge\Arrangement\Skjema\Write as UKMNorgeWrite;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Typer\Type;
use UKMNorge\Innslag\Write as WriteInnslag;
use UKMNorge\Innslag\Titler\Write as WriteTitler;
use UKMNorge\Innslag\Personer\Write as WritePerson;
use UKMNorge\Meta\Write as WriteMeta;
use UKMNorge\Wordpress\Blog;

require_once('UKM/Autoloader.php');

class Write
{
    /**
     * Opprett ny mønstring (Arrangement)
     *
     * @param String [kommune|fylke|land] $type
     * @param Int $eier: fylke_id, eller kommune_id av eier
     * @param Int $sesong
     * @param String $navn
     * @param Array[Kommune] $geografi
     * @param String $path
     * @return Arrangement $created_monstring
     */
    public static function create(String $type, Int $eier_id, Int $sesong, String $navn, array $geografi, String $path)
    {
        // Oppdater loggeren til å bruke riktig PL_ID
        Logger::setPlId(0);

        /**
         *
         * SJEKK INPUT-DATA
         *
         **/
        if (!in_array($type, array('kommune', 'fylke', 'land'))) {
            throw new Exception(
                'Arrangement::create: Ukjent type mønstring "' . $type . '"',
                501003
            );
        }
        if (!is_int($sesong)) {
            throw new Exception(
                'Arrangement::create: Sesong må være integer',
                501004
            );
        }

        if ($type == 'kommune') {
            if (!is_array($geografi)) {
                throw new Exception(
                    'Arrangement::create: Geografiobjekt må være array kommuner, ikke' . (is_object($geografi) ? get_class($geografi) : is_array($geografi) ? 'array' : is_integer($geografi) ? 'integer' : is_string($geografi) ? 'string' : 'ukjent datatype'),
                    501005
                );
            }
            foreach ($geografi as $kommune) {
                if (!Kommune::validateClass($kommune)) {
                    throw new Exception(
                        'Arrangement::create: Alle Geografi->kommuneobjekt må være av typen UKMApi::kommune',
                        501006
                    );
                }
            }
        }

        /**
         *
         * SETT INN RAD I smartukm_place
         *
         **/
        $place = new Insert('smartukm_place');
        $place->add('pl_start', 0);
        $place->add('pl_stop', 0);
        $place->add('pl_public', 0);
        $place->add('pl_missing', 0);
        $place->add('pl_form', 0);
        $place->add('pl_type', $type);
        $place->add('pl_deadline', static::getStandardFrist($sesong, $type));
        $place->add('pl_deadline2', static::getStandardFrist($sesong, $type));

        switch ($type) {
            case 'kommune':
                $kommune = new Kommune($eier_id);
                $place->add('pl_owner_kommune', $eier_id);
                $place->add('pl_owner_fylke', $kommune->getFylke()->getId());
                $place->add('old_pl_fylke', 0);
                $place->add('old_pl_kommune', time());
                break;
            case 'fylke':
                $place->add('pl_owner_kommune', 0);
                $place->add('pl_owner_fylke', $eier_id);
                $place->add('old_pl_fylke', $eier_id);
                $place->add('old_pl_kommune', 0);
                break;
            case 'land':
                $place->add('pl_owner_kommune', 0);
                $place->add('pl_owner_fylke', 0);
                $place->add('old_pl_fylke', 123456789);
                $place->add('old_pl_kommune', 123456789);
                break;
        }

        $navn = str_replace(['UKM ','UKM'],'',$navn);
        $place->add('pl_name', ltrim(rtrim($navn)));
        $place->add('season', $sesong);

        $pl_id = $place->run();
        // Oppdater loggeren til å bruke riktig PL_ID
        Logger::setPlId($pl_id);

        $monstring = new Arrangement($pl_id);

        if ($type == 'kommune') {
            foreach ($geografi as $kommune) {
                $monstring->getKommuner()->leggTil($kommune);
            }
        }

        $monstring->setPath($path);

        self::save($monstring);

        return $monstring;
    }

    /**
     * Hent standard-frist for en gitt sesong
     *
     * @param Int $sesong
     * @param String $type
     * @return void
     */
    public static function getStandardFrist(Int $sesong, String $type)
    {
        if ($type == 'fylke') {
            return DateTime::createFromFormat('d.n.Y H:i:s', '01.03.' . $sesong . ' 23:59:59');
        }
        return DateTime::createFromFormat('d.n.Y H:i:s', '01.01.' . $sesong . ' 23:59:59');
    }

    /**
     * Konverter input-data til DateTime for dato+tid-lagring
     *
     * @param String $date d.m.Y
     * @param String $time H:i, default 00:00
     * @return DateTime
     */
    public static function inputToDateTime(String $date, String $time = '00:00')
    {
        return DateTime::createFromFormat(
            'd.m.Y-H:i',
            $date . '-' . $time
        );
    }

    /**
     * Lagre alle endringer på et arrangement
     *
     * OBS: Skal kun brukes fra WP-admin
     * Det er kun herfra det kan skje (skikkelig), da blogg-oppdateringen krever
     * WP-funksjoner. Kjørt utenfor wp-admin vil den kræsje før den fullfører, men alle 
     * endringer på arrangementet vil være lagret først. Funker nesten, altså.
     * 
     * @param Arrangement $monstring_save
     * @return Bool $success
     * @throws Exception hvis noe feiler
     */
    public static function save($monstring_save)
    {
        // DB-OBJEKT
        $monstring_db = new Arrangement($monstring_save->getId());

        // TABELLER SOM KAN OPPDATERES
        $smartukm_place = new Update(
            'smartukm_place',
            [
                'pl_id' => $monstring_save->getId()
            ]
        );

        // VERDIER SOM KAN OPPDATERES
        $properties = [
            'Navn'                 => ['smartukm_place', 'pl_name', 100],
            'Path'                 => ['smartukm_place', 'pl_link', 110],
            'Uregistrerte'        => ['smartukm_place', 'pl_missing', 108],
            'Publikum'          => ['smartukm_place', 'pl_public', 109],
            'Sted'                => ['smartukm_place', 'pl_place', 101],
            'Start'                => ['smartukm_place', 'pl_start', 102],
            'Stop'              => ['smartukm_place', 'pl_stop', 103],
            'Frist1'            => ['smartukm_place', 'pl_deadline', 106],
            'Frist2'            => ['smartukm_place', 'pl_deadline2', 107],
            'Skjema'            => ['smartukm_place', 'pl_form', 113],
            'Pamelding'         => ['smartukm_place', 'pl_pamelding', 119],
            'GoogleMapData'     => ['smartukm_place', 'pl_location', 122],
            'harVideresending'  => ['smartukm_place', 'pl_videresending', 123],
            'Pamelding'         => ['smartukm_place', 'pl_pamelding', 124],
            'harSkjema'         => ['smartukm_place', 'pl_has_form', 127],
            'Synlig'            => ['smartukm_place', 'pl_visible', 128],
            'Subtype'            => ['smartukm_place', 'pl_subtype', 131]
        ];

        // Eierfylke lagres for kommuner og fylker, men ikke land
        if (in_array($monstring_save->getEierType(), ['kommune', 'fylke'])) {
            $properties['EierFylke'] = ['smartukm_place', 'pl_owner_fylke', 120];
        }

        // Eierkommune lagres kun for kommuner
        if ($monstring_save->getEierType() == 'kommune') {
            $properties['EierKommune'] = ['smartukm_place', 'pl_owner_kommune', 121];
        }

        // LOOP ALLE VERDIER, OG EVT LEGG TIL I SQL
        foreach ($properties as $functionName => $logValues) {

            if (strpos($functionName, 'har') === 0) {
                $function = $functionName;
            } else {
                $function = 'get' . $functionName;
            }
            $table = $logValues[0];
            $field = $logValues[1];
            $action = $logValues[2];
            $sql = $$table;

            #echo '$monstring_db->'.$function.'() != $monstring_save->'.$function.'() => '.
            #    ($monstring_db->$function() != $monstring_save->$function() ? 'true' : 'false') .'<br />';
            try {
                if ($monstring_db->$function() != $monstring_save->$function()) {
                    # Mellomlagre verdi som skal settes
                    $value = $monstring_save->$function();
                    # Fjern UKM fra alle arrangementnavn
                    if( $functionName == 'getNavn' ) {
                        $value = str_replace(['UKM ','UKM'],'',$value);
                    }
                    # Legg til i SQL
                    $sql->add($field, $value);     // SQL satt dynamisk i foreach til $$table
                    # Logg (eller dø) før vi kjører run
                    Logger::log($action, $monstring_save->getId(), $value);
                }
            } catch (Exception $e) {
                // Noen verdier kan ikke lagres, som f.eks fylke:0
                // Lagrer likevel resten
            }
        }

        $res = true; // Fordi smartukm_place->run() vil overskrive hvis det oppstår feil
        if ($smartukm_place->hasChanges()) {
            #echo $smartukm_place->debug();
            $res = $smartukm_place->run();
        }
        if ($res === false) {
            # echo $smartukm_place->getError();
            throw new Exception(
                'Kunne ikke lagre mønstring skikkelig, da lagring av detaljer feilet.',
                501008
            );
        }


        // Hvis lokalmønstring, sjekk og lagre kommunesammensetning
        if ($monstring_save->getEierType() == 'kommune') {
            foreach ($monstring_save->getKommuner()->getAll() as $kommune) {
                if (!$monstring_db->getKommuner()->har($kommune)) {
                    self::_leggTilKommune($monstring_save, $kommune);
                }
            }
            foreach ($monstring_db->getKommuner()->getAll() as $kommune) {
                if (!$monstring_save->getKommuner()->har($kommune)) {
                    self::_fjernKommune($monstring_save, $kommune);
                }
            }
        }

        // Sjekk kontaktpersoner og lagre endringer
        foreach ($monstring_save->getKontaktpersoner()->getAll() as $kontakt) {
            if (!$monstring_db->getKontaktpersoner()->har($kontakt)) {
                self::_leggTilKontaktperson($monstring_save, $kontakt);
            }
        }
        foreach ($monstring_db->getKontaktpersoner()->getAll() as $kontakt) {
            if (!$monstring_save->getKontaktpersoner()->har($kontakt)) {
                self::_fjernKontaktperson($monstring_save, $kontakt);
            }
        }

        // Sjekk tillatte typer innslag og lagre endringer
        foreach ($monstring_save->getInnslagtyper()->getAll() as $innslag_type) {
            // Fordi mønstringen aldri skal ha 0 innslag-typer returneres et
            // standardsett (se Typer::getPakrevd())
            // Hvis man da lagrer et subset av dette, uten å legge til en ikke påkrevd type,
            // vil db-objektet alltid ha alle, og en insert vil aldri skje.
            // Hvis insert aldri skjer, vil mønstringen ha 0 innslag-typer, som igjen
            // loader den med standard-settet.
            try {
                self::_leggTilInnslagtype($monstring_save, $innslag_type);
            } catch (Exception $e) { }
        }
        foreach ($monstring_db->getInnslagtyper()->getAll() as $innslag_type) {
            if (!$monstring_save->getInnslagtyper()->har($innslag_type)) {
                self::_fjernInnslagtype($monstring_save, $innslag_type);
            }
        }

        // Sjekk hvem som får videresende, og lagre endringer
        if ($monstring_save->harVideresending()) {
            if ($monstring_save->getVideresending()->harAvsendere()) {
                foreach ($monstring_save->getVideresending()->getAvsendere() as $avsender) {
                    if (!$monstring_db->getVideresending()->harAvsender($avsender->getId())) {
                        self::_leggTilVideresendingAvsender($monstring_save, $avsender);
                    }
                }
            }

            if ($monstring_db->getVideresending()->harAvsendere()) {
                foreach ($monstring_db->getVideresending()->getAvsendere() as $avsender) {
                    if (!$monstring_save->getVideresending()->harAvsender($avsender->getId())) {
                        self::_fjernVideresendingAvsender($monstring_save, $avsender);
                    }
                }
            }
        }

        // Arrangementet har endret navn. Oppdater relasjon mellom arrangement og innslag
        // da arrangementsnavnet mellomlagres her
        // Skulle det feile varsles ingen (dette er IKKE kritisk, da det håndterer en ufarlig edge-case)
        if ($monstring_save->getNavn() != $monstring_db->getNavn()) {
            $rel = new Update(
                'ukm_rel_arrangement_innslag',
                [
                    'fra_arrangement_id' => $monstring_save->getId()
                ]
            );
            $rel->add('fra_arrangement_navn', $monstring_save->getNavn());
            $rel->run();
        }

        // Oppdater tilhørende blogg
        // Ved sletting av blogg settes path==null. Ettersom dette lagres før vi kommer hit,
        // vil getIdByPath feile (da path==null). Hopp over denne i slike tilfeller.
        if (!empty($monstring_save->getPath())) {
            try {
                Blog::setArrangementData(Blog::getIdByPath($monstring_save->getPath()), $monstring_save);
            } catch (Exception $e) {
                // 172007 = fant ingen blogg (som er naturlig når den opprettes, f.eks.)
                // 172010 = vi er ikke i wordpress-environment
                if ($e->getCode() != 172007 && $e->getCode() != 172010) {
                    throw $e;
                }
            }
        }

        // Lagre meta-data
        if( is_array($monstring_save->getMetaCollection()->getAll())) {
            foreach ($monstring_save->getMetaCollection()->getAll() as $meta) {
                WriteMeta::set($meta);
            }
        }

        return $res;
    }

    /**
     * Slett et arrangement
     *
     * @param Arrangement $arrangement
     * @return void
     */
    public static function slett(Arrangement $arrangement)
    {
        Logger::log(
            129,
            $arrangement->getId(),
            $arrangement->getId() . ': ' . $arrangement->getNavn()
        );

        $arrangement_navn = $arrangement->getNavn();

        // MERK SOM SLETTET PÅ GAMLE-MÅTEN
        $arrangement->setNavn('SLETTET: ' . $arrangement->getNavn());
        $arrangement->setPath(NULL);
        $arrangement->setSlettet(true);
        static::save($arrangement);

        // Logg navne-endring
        Logger::log(
            115,
            $arrangement->getId(),
            $arrangement_navn
        );

        // FAKTISK MERK SOM SLETTET
        $sql = new Update(
            'smartukm_place',
            [
                'pl_id' => $arrangement->getId()
            ]
        );
        $sql->add('pl_deleted', 'true');
        $res = $sql->run();

        // Fjern kommuner fra arrangementet
        if ($arrangement->getEierType() == 'kommune') {
            foreach ($arrangement->getKommuner()->getAll() as $kommune) {
                self::_fjernKommune($arrangement, $kommune);
            }
        }

        return $arrangement;
    }

    /**
     * Gjenopprett et slettet arrangement
     *
     * OBS: Gjenoppretter ikke kommune-relasjoner!
     * 
     * @param Int $arrangement_id
     * @return Arrangement
     */
    public static function gjenopprett(Int $arrangement_id)
    {
        $arrangement = new Arrangement($arrangement_id);

        Logger::log(
            130,
            $arrangement->getId(),
            $arrangement->getId() . ': ' . $arrangement->getNavn()
        );

        $sql = new Update(
            'smartukm_place',
            [
                'pl_id' => $arrangement->getId()
            ]
        );
        $sql->add('pl_deleted', 'false');
        $res = $sql->run();

        // ENDRE TILBAKE NAVNET
        $arrangement->setNavn(str_replace('SLETTET: ', '', $arrangement->getNavn()));
        $arrangement->setPath(Blog::sanitizePath($arrangement->getNavn()));
        $arrangement->setSlettet(false);
        static::save($arrangement);

        return $arrangement;
    }

    /**
     * Generer path for et arrangement
     *
     * @param [type] $type
     * @param [type] $geografi
     * @param [type] $sesong
     * @param boolean $skipCheck
     * @return void
     */
    public static function generatePath($type, $geografi, $sesong, $skipCheck = false)
    {
        switch ($type) {
            case 'kommune':
                // Legg til kommunerelasjoner og bygg link
                $kommuner = [];
                foreach ($geografi as $kommune) {
                    $kommuner[] = $kommune->getURLsafe();
                }
                sort($kommuner);
                $link = implode('-', $kommuner);

                if ($skipCheck) {
                    return $link;
                }
                // Sjekk om linken er i bruk for gitt sesong
                $linkCheck = new Query(
                    "SELECT `pl_id`
                                         FROM `smartukm_place`
                                         WHERE `pl_link` = '#link'
                                         AND `season` = '#season'",
                    array(
                        'link' => $link,
                        'season' => $sesong,
                    )
                );
                $linkExists = $linkCheck->run('field', 'pl_id');
                if (false !== $linkExists && is_numeric($linkExists)) {
                    $fylke = $kommune->getFylke(); // Bruker siste kommune fra foreach
                    $link = $fylke->getURLsafe() . '-' . $link;
                }
                break;
            case 'fylke':
                $link = $geografi->getURLsafe();
                break;
            case 'land':
                $link = 'festivalen';
                break;
            case 'default':
                throw new Exception(
                    'Arrangement\Write::createLink() kan ikke genere link for ukjent type arrangement!',
                    501009
                );
        }
        return $link;
    }

    /**
     * Avlys mønstring
     * (brukes kun i Wordpress-context!)
     * 
     * Denne skal kun brukes i Wordpress-context
     * da bloggen må endres for at alt skal fungere som ønsket.
     *
     * @param Arrangement $monstring
     **/
    public static function avlys(Arrangement $arrangement)
    {
        // Bloggen håndterer selv sletting, flytting, om det er en kommune-side osv.
        try {
            Blog::avlys(
                Blog::getIdByPath($arrangement->getPath())
            );
        } catch( Exception $e ) {
            /**
             * 572001: bloggen antas å være slettet tidligere
             * pga manglende PL_ID
             */
            if( $e->getCode() != 572001 ) {
                throw $e;
            }
        }
        // Slett arrangementet
        self::slett($arrangement);

        // Hvis kommunen nå har bare ett arrangement, må vi oppdatere
        // arrangementet til å ha kommune-path
        if ($arrangement->getEierType() == 'kommune') {
            $omrade = $arrangement->getEierOmrade();
            $eier = $arrangement->getEier();

            $count = 0;
            $arrangement_som_skal_overta = false;
            foreach ($omrade->getArrangementer()->getAll() as $annet_arrangement) {
                if ($annet_arrangement->getId() != $arrangement->getId()) {
                    $count++;
                    if (!$annet_arrangement->erFellesmonstring()) {
                        $arrangement_som_skal_overta = $annet_arrangement;
                    }
                }
            }
            if ($count == 1 && $arrangement_som_skal_overta) {
                $arrangement_som_skal_overta->setPath(trim($eier->getPath(), '/'));
                self::save($arrangement_som_skal_overta);
            }
        }
        return $arrangement;
    }

    /**
     * Gi et arrangement tillatelse til å videresende innslag
     *
     * @param Arrangement $monstring_save
     * @param Avsender $avsender
     * @return void
     */
    private static function _leggTilVideresendingAvsender($monstring_save, $avsender)
    {
        try {
            self::_controlMonstring($monstring_save);
            self::_controlVideresending($avsender);
        } catch (Exception $e) {
            throw new Exception(
                'Kan ikke legge til avsender da ' . $e->getMessage(),
                501014
            );
        }

        $test = new Query(
            "SELECT `id`
                FROM `ukm_rel_pl_videresending`
                WHERE `pl_id_receiver` = '#arrangement'
                AND `pl_id_sender` = '#avsender'",
            [
                'arrangement' => $monstring_save->getId(),
                'avsender' => $avsender->getId()
            ]
        );
        $testRes = $test->run();
        if (is_numeric($testRes) && $testRes > 0) {
            return true;
        }

        $insert = new Insert('ukm_rel_pl_videresending');
        $insert->add('pl_id_receiver', $monstring_save->getId());
        $insert->add('pl_id_sender', $avsender->getId());
        $res = $insert->run();

        if (!$res) {
            return false;
        }

        Logger::log(
            125,
            $monstring_save->getId(),
            $avsender->getId() . ': ' . $avsender->getNavn()
        );

        return true;
    }

    /**
     * Trekk tilbake et arrangements tillatelse til å videresende innslag
     *
     * @param Arrangement $monstring_save
     * @param Avsender $avsender
     * @return void
     */
    private static function _fjernVideresendingAvsender($monstring_save, $avsender)
    {
        try {
            self::_controlMonstring($monstring_save);
            self::_controlVideresending($avsender);
        } catch (Exception $e) {
            throw new Exception(
                'Kan ikke fjerne avsender da ' . $e->getMessage(),
                501015
            );
        }

        $delete = new Delete(
            'ukm_rel_pl_videresending',
            [
                'pl_id_receiver' => $monstring_save->getId(),
                'pl_id_sender' => $avsender->getId()
            ]
        );
        $res = $delete->run();

        if (!$res) {
            return false;
        }


        Logger::log(
            126,
            $monstring_save->getId(),
            $avsender->getId() . ': ' . $avsender->getNavn()
        );

        return true;
    }

    /**
     * Sjekk at mottatt avsender er av riktig objekt
     *
     * @param Any $avsender
     * @return Bool $matches
     */
    private static function _controlVideresending($avsender)
    {
        if (!get_class($avsender) == 'Avsender') {
            throw new Exception(
                'Avsender må være av typen Arrangement',
                501016
            );
        }
    }

    /**
     * Legg kontaktperson til mønstringen
     * 
     * Sjekker at databaseraden ikke allerede eksisterer, og
     * setter inn ny rad ved behov
     *
     * @param Arrangement $monstring
     * @param kontakt_v2 $kontakt
     * @return bool $sucess
     **/
    private static function _leggTilKontaktperson($monstring_save, $kontakt)
    {
        try {
            self::_controlMonstring($monstring_save);
            self::_controlKontaktperson($kontakt);
        } catch (Exception $e) {
            throw new Exception(
                'Kan ikke legge til kontaktperson da ' . $e->getMessage(),
                501017
            );
        }

        $test = new Query(
            "
                SELECT `ab_id`
                FROM `smartukm_rel_pl_ab`
                WHERE `pl_id` = '#pl_id'
                AND `ab_id` = '#ab_id'",
            [
                'pl_id' => $monstring_save->getId(),
                'ab_id' => $kontakt->getId()
            ]
        );
        $testRes = $test->run('field', 'ab_id');
        if (is_numeric($testRes) && $testRes > 0) {
            return true;
        }

        $rel_pl_ab = new Insert('smartukm_rel_pl_ab');
        $rel_pl_ab->add('pl_id', $monstring_save->getId());
        $rel_pl_ab->add('ab_id', $kontakt->getId());
        $rel_pl_ab->add('order', time());
        $res = $rel_pl_ab->run();

        if (!$res) {
            return false;
        }

        Logger::log(
            111,
            $monstring_save->getId(),
            $kontakt->getId() . ': ' . $kontakt->getNavn()
        );
        return true;
    }

    /**
     * Fjern en kontaktperson fra mønstringen
     * 
     * Sletter databaseraden hvis den eksisterer
     *
     * @param Arrangement $monstring
     * @param kontakt_v2 $kontakt
     * @return void
     **/
    private static function _fjernKontaktperson($monstring_save, $kontakt)
    {
        try {
            self::_controlMonstring($monstring_save);
            self::_controlKontaktperson($kontakt);
        } catch (Exception $e) {
            throw new Exception(
                'Kan ikke fjerne kontaktperson da ' . $e->getMessage(),
                501018
            );
        }

        $rel_pl_ab = new Delete(
            'smartukm_rel_pl_ab',
            [
                'pl_id' => $monstring_save->getId(),
                'ab_id' => $kontakt->getId()
            ]
        );
        $res = $rel_pl_ab->run();

        if (!$res) {
            return false;
        }

        Logger::log(
            116,
            $monstring_save->getId(),
            $kontakt->getId() . ': ' . $kontakt->getNavn()
        );

        return true;
    }

    /**
     * Legg til en kommune i mønstringen
     * Sjekker at databaseraden ikke allerede eksisterer, og
     * setter inn ny rad ved behov
     * 
     * @param Arrangement $monstring
     * @param kommune $kommune
     * 
     * @return bool $result
     */
    private static function _leggTilKommune($monstring_save, $kommune)
    {
        try {
            self::_controlMonstring($monstring_save);
            if (!Arrangement::validateClass($monstring_save)) {
                throw new Exception(
                    'mønstring ikke er lokal-mønstring',
                    501019
                );
            }
            self::_controlKommune($kommune);
        } catch (Exception $e) {
            echo '<pre>';
            throw new Exception(
                'Kan ikke legge til kommune da ' . $e->getMessage(),
                501020
            );
        }

        $test = new Query(
            "
                SELECT `k_id`
                FROM `smartukm_rel_pl_k`
                WHERE `pl_id` = '#pl_id'
                AND `k_id` = '#k_id'",
            [
                'pl_id' => $monstring_save->getId(),
                'k_id' => $kommune->getId()
            ]
        );
        $resTest = $test->run('field', 'k_id');
        if (is_numeric($resTest) && $resTest == $kommune->getId()) {
            return true;
        }

        $rel_pl_k = new Insert('smartukm_rel_pl_k');
        $rel_pl_k->add('pl_id', $monstring_save->getId());
        $rel_pl_k->add('season', $monstring_save->getSesong());
        $rel_pl_k->add('k_id', $kommune->getId());
        $res = $rel_pl_k->run();

        if (!$res) {
            return false;
        }

        Logger::log(
            112,
            $monstring_save->getId(),
            $kommune->getId() . ': ' . $kommune->getNavn()
        );
        return true;
    }

    /**
     * Fjern kommune fra mønstringen
     * 
     * Sletter relasjon fra databasen hvis den finnes
     * 
     * @param Arrangement $arrangement
     * @param Kommune $kommune
     * @return Bool 
     * @throws Exception
     */
    private static function _fjernKommune(Arrangement $arrangement, Kommune $kommune)
    {
        try {
            self::_controlMonstring($arrangement);
            if ($arrangement->getType() != 'kommune') {
                throw new Exception(
                    'mønstring ikke er lokal-mønstring',
                    501022
                );
            }
            self::_controlKommune($kommune);
        } catch (Exception $e) {
            throw new Exception(
                'Kan ikke fjerne kommune da ' . $e->getMessage(),
                501021
            );
        }

        // Hvis mønstringen på dette tidspunktet
        // fortsattt har kommunen i kommune-collection
        // er det på høy tid å fjerne den.
        // (avlys kan finne på å gjøre dette tror Marius (26.10.2018))
        if ($arrangement->getKommuner()->har($kommune)) {
            $arrangement->getKommuner()->fjern($kommune);
        }

        $rel_pl_k = new Delete(
            'smartukm_rel_pl_k',
            [
                'pl_id' => $arrangement->getId(),
                'k_id' => $kommune->getId(),
                'season' => $arrangement->getSesong(),
            ]
        );
        $res = $rel_pl_k->run();

        Logger::log(
            114,
            $arrangement->getId(),
            $kommune->getId() . ': ' . $kommune->getNavn()
        );
        return true;
    }

    /**
     * Kontroller at gitt objekt er mønstring
     *
     * @param Any $arrangement
     * @throws Exception
     * @return Bool true
     */
    private static function _controlMonstring($monstring)
    {
        if (!Arrangement::validateClass($monstring)) {
            throw new Exception(
                'mønstring ikke er objekt av typen Arrangement / Arrangement. Fikk (' . get_class($monstring) . ')',
                501023
            );
        }
        if (!is_numeric($monstring->getId())) {
            throw new Exception(
                'mønstring ikke har numerisk ID',
                501024
            );
        }
        if (!is_numeric($monstring->getSesong())) {
            throw new Exception(
                'mønstringen ikke har numerisk sesong-verdi',
                501025
            );
        }
        return true;
    }

    /**
     * Kontroller at gitt objekt er kontaktperson
     *
     * @param Any $kontakt
     * @throws Exception
     * @return Bool true
     */
    private static function _controlKontaktperson($kontakt)
    {
        if (!Kontaktperson::validateClass($kontakt)) {
            throw new Exception(
                'kontakt ikke er objekt av typen Kontaktperson',
                501026
            );
        }
        if (!is_numeric($kontakt->getId()) && $kontakt->getId() > 0) {
            throw new Exception(
                'kontakt ikke har numerisk id',
                501027
            );
        }
    }

    /**
     * Kontroller at gitt objekt er kommune
     *
     * @param Any $kontakt
     * @throws Exception
     * @return Bool true
     */
    private static function _controlKommune($kommune)
    {
        if (!Kommune::validateClass($kommune)) {
            throw new Exception(
                'kommune ikke er objekt av typen kommune',
                501028
            );
        }
        if (!is_numeric($kommune->getId())) {
            throw new Exception(
                'kommune ikke har numerisk id',
                501029
            );
        }
        return true;
    }

    /**
     * Legg til en ny type innslag til arrangementet
     * 
     * Sjekker at databaseraden ikke allerede eksisterer, og
     * setter inn ny rad ved behov
     *
     * @param Arrangement $monstring
     * @param Type $innslag_type
     * @return bool $sucess
     **/
    private static function _leggTilInnslagtype(Arrangement $monstring_save, Type $innslag_type)
    {
        try {
            self::_controlMonstring($monstring_save);
        } catch (Exception $e) {
            throw new Exception(
                'Kan ikke legge til innslagstype da ' . $e->getMessage(),
                501030
            );
        }

        $test = new Query(
            "
                SELECT `id`
                FROM `ukm_rel_arrangement_innslag_type`
                WHERE `pl_id` = '#pl_id'
                AND `type_id` = '#type_id'",
            [
                'pl_id' => $monstring_save->getId(),
                'type_id' => $innslag_type->getKey()
            ]
        );
        $testRes = $test->getField();
        if (is_numeric($testRes) && $testRes > 0) {
            return true;
        }

        $insert = new Insert('ukm_rel_arrangement_innslag_type');
        $insert->add('pl_id', $monstring_save->getId());
        $insert->add('type_id', $innslag_type->getKey());
        $res = $insert->run();

        if (!$res) {
            return false;
        }

        Logger::log(
            117,
            $monstring_save->getId(),
            $innslag_type->getKey()
        );
        return true;
    }

    /**
     * Faktisk legg til en ny type innslag til mønstringen (db-modifier)
     * 
     * Sjekker at databaseraden ikke allerede eksisterer, og
     * setter inn ny rad ved behov
     *
     * @param Arrangement $monstring
     * @param Type $innslag_type
     * @return bool $sucess
     **/
    private static function _fjernInnslagtype(Arrangement $monstring_save, Type $innslag_type)
    {
        try {
            self::_controlMonstring($monstring_save);
        } catch (Exception $e) {
            throw new Exception(
                'Kan ikke fjerne innslagstype da ' . $e->getMessage(),
                501031
            );
        }

        $delete = new Delete(
            'ukm_rel_arrangement_innslag_type',
            [
                'pl_id' => $monstring_save->getId(),
                'type_id' => $innslag_type->getKey()
            ]
        );
        $res = $delete->run();

        if (!$res) {
            return false;
        }

        Logger::log(
            118,
            $monstring_save->getId(),
            $innslag_type->getKey()
        );
        return true;
    }


    /**
     * Legg til innslag i arrangement
     *
     * Bruk Innslag\Write::create for å legge til på lokalnivå !
     * 
     * @param Arrangement $arrangement
     * @param Innslag $innslag
     * @param Arrangement $fra_arrangement
     * @return void
     * @throws Exception
     **/
    public static function leggTilInnslag(Arrangement $arrangement, Innslag $innslag, Arrangement $fra_arrangement)
    {

        Logger::log(318, $innslag->getId(), $innslag->getContext()->getMonstring()->getId());

        // Når innslaget opprettes, inputter vi samme arrangement både som 
        // mottaker og avsender.
        if ($arrangement->getId() == $fra_arrangement->getId()) {
            $fra_id = 0;
            $fra_navn = $innslag->getKommune()->getNavn();
        }
        // Vi er i ferd med å videresende innslaget
        else {
            $fra_id = $fra_arrangement->getId();
            $fra_navn = $fra_arrangement->getNavn();
        }

        // Opprett relasjon mellom innslaget og arrangementet
        // Påkrevd f.o.m. 2020
        $relasjon = new Insert('ukm_rel_arrangement_innslag');
        $relasjon->add('innslag_id', (int) $innslag->getId());
        $relasjon->add('arrangement_id', $arrangement->getId());
        $relasjon->add('fra_arrangement_id', $fra_id);
        $relasjon->add('fra_arrangement_navn', $fra_navn);

        try {
            $res = $relasjon->run();
            if (!$res) {
                throw new Exception(
                    'Klarte ikke å melde på innslaget',
                    505010
                );
            }
        } catch (Exception $e) {
            if ($e->getCode() == 901001) {
                $test = new Query(
                    "SELECT `id` 
                    FROM `ukm_rel_arrangement_innslag`
                    WHERE `innslag_id` = '#innslag'
                    AND `arrangement_id` = '#arrangement'",
                    [
                        'innslag' => $innslag->getId(),
                        'arrangement' => $arrangement->getId()
                    ]
                );
                $test = $test->getField();
                if (is_null($test)) {
                    throw $e;
                }
            } else {
                throw $e;
            }
        }

        // Sett inn gammel relasjon også
        $rel = new Insert('smartukm_rel_pl_b');
        $rel->add('pl_id', $arrangement->getId());
        $rel->add('b_id', $innslag->getId());
        $rel->add('season', $arrangement->getSesong());

        $relres = $rel->run();
        if (!$relres) {
            throw new Exception(
                "Klarte ikke å melde på det nye innslaget til mønstringen.",
                505021
            );
        }

        // Når det er enkeltperson-innslag, skal personen automatisk videresendes
        if ($innslag->getType()->erEnkeltPerson()) {

            // Hent fra riktig context for å legge til på nytt (aka videresende)
            $person = $innslag->getPersoner()->getSingle();
            // I det innslaget opprettes vil vi ikke finne personen enda, 
            // da den snart legges til 
            // (2020-02-19: det er i alle fall teorien om hvorfor det feiler)
            if ($person) {
                $innslag = $arrangement->getInnslag()->get($innslag->getId(), true);
                $innslag->getPersoner()->leggTil($person);
                $person = $innslag->getPersoner()->get($person->getId());
                WritePerson::leggTil($person); # aka persist
            }
        }
        // For noen innslag skal alle personer automatisk følge innslaget
        elseif ($innslag->getType()->harAutomatiskVideresendingAvPersoner()) {
            $personer = [];
            foreach ($innslag->getPersoner()->getAll() as $person) {
                $personer[] = $person;
            }

            // Reload innslag, og legg til
            $innslag = $arrangement->getInnslag()->get($innslag->getId(), true);
            foreach ($personer as $person) {
                $innslag->getPersoner()->leggTil($person);
                $person = $innslag->getPersoner()->get($person->getId());
                WritePerson::leggTil($person); # aka persist
            }
        }
    }

    /**
     * Fjern innslag fra arrangement
     * 
     * Bruk Innslag\Write::meldAv() for å fjerne fra lokalnivå
     * @see WriteInnslag::meldAv()
     *
     * @param Innslag $innslag
     * @return void
     */
    public static function fjernInnslag(Innslag $innslag)
    {

        Logger::log(319, $innslag->getId(), $innslag->getContext()->getMonstring()->getId());

        // Fjern fra alle forestillinger på mønstringen
        WriteHendelse::fjernInnslagFraAlleForestillingerIMonstring($innslag);

        // Fjern personer pre 2020
        if ($innslag->getSesong() < 2020 && $innslag->getType()->getId() != 1) {
            // Meld av alle personer hvis dette er innslag hvor man kan velge personer som følger innslaget
            foreach ($innslag->getPersoner()->getAllInkludertIkkePameldte() as $person) {
                $innslag->getPersoner()->fjern($person);
            }
            WriteInnslag::savePersoner($innslag);
        }
        // Fjern personer 2020
        else {
            // Bruker ikke getAll() i stedet for getAllInkludertIkkePameldte()
            // da vi kun skal melde av personer påmeldt dette arrangementet
            foreach ($innslag->getPersoner()->getAll() as $person) {
                $innslag->getPersoner()->fjern($person);
            }
            WriteInnslag::savePersoner($innslag);
        }

        // Meld av alle titler
        if ($innslag->getType()->harTitler()) {
            // Bruker ikke getAll() i stedet for getAllInkludertIkkePameldte()
            // da vi kun skal melde av titler påmeldt dette arrangementet
            foreach ($innslag->getTitler()->getAll() as $tittel) {
                $innslag->getTitler()->fjern($tittel);
            }
            WriteInnslag::saveTitler($innslag);
        }

        // Fjern innslagets relasjon til arrangementet
        # Relatert https://github.com/UKMNorge/UKMapi/issues/46
        $relasjon = new Delete(
            'ukm_rel_arrangement_innslag',
            [
                'innslag_id' => $innslag->getId(),
                'arrangement_id' => $innslag->getContext()->getMonstring()->getId()
            ]
        );
        $res = $relasjon->run();

        // Fjern gammel relasjon videresendingen av innslaget
        $delete = new Delete(
            'smartukm_fylkestep',
            [
                'pl_id' => $innslag->getContext()->getMonstring()->getId(),
                'b_id'    => $innslag->getId(),
            ]
        );

        $res = $delete->run();

        // Fjern gammel relasjon til personer videresendt til denne mønstringen
        $slett_relasjon = new Delete(
            'smartukm_rel_pl_b',
            [
                'pl_id'        => $innslag->getContext()->getMonstring()->getId(),
                'b_id'        => $innslag->getId(),
                'season'    => $innslag->getContext()->getMonstring()->getSesong(),
            ]
        );
        $slett_relasjon->run();


        if ($res) {
            return true;
        }

        return $innslag;
    }

    /**
     * Er gitt objekt gyldig Arrangement::Write-objekt?
     *
     * @param Any $object
     * @return Bool
     */
    public static function validateClass($object)
    {
        return is_object($object) &&
            in_array(
                get_class($object),
                ['UKMNorge\Arrangement\Write', 'write_monstring']
            );
    }
}
