<?php

namespace UKMNorge\Arrangement;

use UKMNorge\Log\Logger;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Geografi\Fylke;
use UKMNorge\Geografi\Kommune;

use Exception;
use DateTime;
use UKMNorge\Arrangement\Kontaktperson\Kontaktperson;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Query;

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
    public static function create($type, $eier_id, $sesong, $navn, $geografi, $path)
    {
        // Oppdater loggeren til å bruke riktig PL_ID
        Logger::setPlId(0);

        /**
         *
         * SJEKK INPUT-DATA
         *
         **/
        if (!in_array($type, array('kommune', 'fylke', 'land'))) {
            throw new Exception('Arrangement::create: Ukjent type mønstring "' . $type . '"');
        }
        if (!is_int($sesong)) {
            throw new Exception('Arrangement::create: Sesong må være integer');
        }

        switch ($type) {
            case 'kommune':
                if (!is_array($geografi)) {
                    throw new Exception(
                        'Arrangement::create: Geografiobjekt må være array kommuner, ikke' . (is_object($geografi) ? get_class($geografi) : is_array($geografi) ? 'array' : is_integer($geografi) ? 'integer' : is_string($geografi) ? 'string' : 'ukjent datatype')
                    );
                }
                foreach ($geografi as $kommune) {
                    if (!Kommune::validateClass($kommune)) {
                        throw new Exception('Arrangement::create: Alle Geografi->kommuneobjekt må være av typen UKMApi::kommune');
                    }
                }
                break;
            case 'fylke':
                if (!Fylke::validateClass($geografi)) {
                    throw new Exception('Arrangement::create: Geografiobjekt må være av typen UKMApi::fylke');
                }
                break;
            case 'land':
                break;
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

        $place->add('pl_name', ltrim(rtrim($navn)));
        $place->add('season', $sesong);

        $pl_id = $place->run();
        // Oppdater loggeren til å bruke riktig PL_ID
        Logger::setPlId($pl_id);

        $monstring = new Arrangement($pl_id);


        foreach ($geografi as $kommune) {
            $monstring->getKommuner()->leggTil($kommune);
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


    public static function save($monstring_save)
    {
        // DB-OBJEKT
        $monstring_db = new Arrangement($monstring_save->getId());

        // TABELLER SOM KAN OPPDATERES
        $smartukm_place = new Insert(
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
            'EierFylke'         => ['smartukm_place', 'pl_owner_fylke', 120],
            'EierKommune'       => ['smartukm_place', 'pl_owner_kommune', 121],
            'GoogleMapData'     => ['smartukm_place', 'pl_location', 122],
            'harVideresending'  => ['smartukm_place', 'pl_videresending', 123],
            'Pamelding'         => ['smartukm_place', 'pl_pamelding', 124],
            'harSkjema'         => ['smartukm_place', 'pl_has_form', 127],
            'Synlig'            => ['smartukm_place', 'pl_visible', 128]
        ];

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
            throw new Exception('Kunne ikke lagre mønstring skikkelig, da lagring av detaljer feilet.');
        }


        // Hvis lokalmønstring, sjekk og lagre kommunesammensetning
        if ($monstring_save->getType() == 'kommune') {
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
            if (!$monstring_db->getInnslagtyper()->har($innslag_type)) {
                self::_leggTilInnslagtype($monstring_save, $innslag_type);
            }
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

        return $res;
    }

    public static function _leggTilVideresendingAvsender($monstring_save, $avsender)
    {
        try {
            self::controlMonstring($monstring_save);
            self::controlVideresending($avsender);
        } catch (Exception $e) {
            throw new Exception('Kan ikke legge til avsender da ' . $e->getMessage());
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

    public static function _fjernVideresendingAvsender($monstring_save, $avsender)
    {
        try {
            self::controlMonstring($monstring_save);
            self::controlVideresending($avsender);
        } catch (Exception $e) {
            throw new Exception('Kan ikke legge til avsender da ' . $e->getMessage());
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

    public static function controlVideresending($avsender)
    {
        if (!get_class($avsender) == 'Avsender') {
            throw new Exception('Avsender må være av typen Arrangement');
        }
    }


    /**
     * Faktisk legg til en kontaktperson til mønstringen (db-modifier)
     * 
     * Sjekker at databaseraden ikke allerede eksisterer, og
     * setter inn ny rad ved behov
     *
     * @param Arrangement $monstring
     * @param kontakt_v2 $kontakt
     * @return bool $sucess
     **/
    public static function _leggTilKontaktperson($monstring_save, $kontakt)
    {
        try {
            self::controlMonstring($monstring_save);
            self::controlKontaktperson($kontakt);
        } catch (Exception $e) {
            throw new Exception('Kan ikke legge til kontaktperson da ' . $e->getMessage());
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
     * Faktisk fjern en kontaktperson fra mønstringen (db-modifier)
     * 
     * Sletter databaseraden hvis den eksisterer
     *
     * @param Arrangement $monstring
     * @param kontakt_v2 $kontakt
     * @return void
     **/
    public static function _fjernKontaktperson($monstring_save, $kontakt)
    {
        try {
            self::controlMonstring($monstring_save);
            self::controlKontaktperson($kontakt);
        } catch (Exception $e) {
            throw new Exception('Kan ikke fjerne kontaktperson da ' . $e->getMessage());
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
     * Faktisk legg til en kommune i mønstringen (db-modifier)
     * 
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
            self::controlMonstring($monstring_save);
            if ( !Arrangement::validateClass($monstring_save)) {
                throw new Exception('mønstring ikke er lokal-mønstring');
            }
            self::controlKommune($kommune);
        } catch (Exception $e) {
            echo '<pre>';
            throw new Exception('Kan ikke legge til kommune da ' . $e->getMessage());
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
     * Faktisk fjern en kommune fra mønstringen (db-modifier)
     * 
     * Sletter databaseraden hvis den finnes
     * 
     * @param Arrangement $monstring
     * @param kommune $kommune
     * @return void
     */
    private static function _fjernKommune($monstring_save, $kommune)
    {
        try {
            self::controlMonstring($monstring_save);
            if ($monstring_save->getType() != 'kommune') {
                throw new Exception('mønstring ikke er lokal-mønstring');
            }
            self::controlKommune($kommune);
        } catch (Exception $e) {
            throw new Exception('Kan ikke fjerne kommune da ' . $e->getMessage());
        }

        // Hvis mønstringen på dette tidspunktet
        // fortsattt har kommunen i kommune-collection
        // er det på høy tid å fjerne den.
        // (avlys kan finne på å gjøre dette tror Marius (26.10.2018))
        if ($monstring_save->getKommuner()->har($kommune)) {
            $monstring_save->getKommuner()->fjern($kommune);
        }

        $rel_pl_k = new Delete(
            'smartukm_rel_pl_k',
            [
                'pl_id' => $monstring_save->getId(),
                'k_id' => $kommune->getId(),
                'season' => $monstring_save->getSesong(),
            ]
        );
        $res = $rel_pl_k->run();

        Logger::log(
            114,
            $monstring_save->getId(),
            $kommune->getId() . ': ' . $kommune->getNavn()
        );
    }

    /**
     * avlys mønstring
     * 
     * !! OBS, OBS !!
     * Denne skal kun benyttes fra UKM Norge-admin,
     * da bloggen må endres for at alt skal fungere som ønsket.
     * !! OBS, OBS !! 
     *
     * @param Arrangement $monstring
     **/
    public static function avlys($monstring)
    {
        if ($monstring->getType() != 'kommune') {
            throw new Exception('Mønstring: kun lokalmønstringer kan avlyses');
        }
        if (!$monstring->erSingelmonstring()) {
            throw new Exception('Mønstring: kun enkeltmønstringer kan avlyses');
        }
        if (!is_numeric($monstring->getId())) {
            throw new Exception('Mønstring: kan ikke fjerne kommune da mønstring ikke har numerisk ID');
        }
        if (!is_numeric($monstring->getSesong())) {
            throw new Exception('Mønstring: kan ikke fjerne kommune da sesong ikke har numerisk verdi');
        }

        self::_fjernKommune($monstring, $monstring->getKommune());

        // Fjern databasefelter som identifiserer mønstringen ("soft delete")
        $monstringsnavn = $monstring->getNavn();
        $monstring->setNavn('SLETTET: ' . $monstring->getNavn());
        $monstring->setPath(NULL);
        self::save($monstring);

        Logger::log(
            115,
            $monstring->getId(),
            $monstringsnavn
        );

        return $monstring;
    }

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
                throw new Exception('WRITE_MONSTRING::createLink() kan ikke genere link for ukjent type mønstring!');
        }
        return $link;
    }


    public static function controlMonstring($monstring)
    {
        if (!Arrangement::validateClass($monstring)){
            throw new Exception('mønstring ikke er objekt av typen Arrangement / Arrangement. Fikk (' . get_class($monstring) . ')');
        }
        if (!is_numeric($monstring->getId())) {
            throw new Exception('mønstring ikke har numerisk ID');
        }
        if (!is_numeric($monstring->getSesong())) {
            throw new Exception('mønstringen ikke har numerisk sesong-verdi');
        }
    }

    public static function controlKontaktperson($kontakt)
    {
        if (!Kontaktperson::validateClass($kontakt)) {
            throw new Exception('kontakt ikke er objekt av typen Kontaktperson');
        }
        if (!is_numeric($kontakt->getId()) && $kontakt->getId() > 0) {
            throw new Exception('kontakt ikke har numerisk id');
        }
    }

    public static function controlKommune($kommune)
    {
        if ( !Kommune::validateClass($kommune) ) {
            throw new Exception('kommune ikke er objekt av typen kommune');
        }
        if (!is_numeric($kommune->getId())) {
            throw new Exception('kommune ikke har numerisk id');
        }
    }

    /**
     * DEPRECATED: addKommune
     * Endre kommuner direkte på mønstringen, og kall write_monstring::save( $monstring )
     * @param kommune $kommune
     **/
    public function leggTilKommune($kommune)
    {
        self::addKommune($kommune);
    }
    public function addKommune($kommune)
    {
        die('DEPRECATED: Endre kommuner direkte på mønstringen, og kall  write_monstring::save( $monstring )');
    }
    /**
     * DEPRECATED: fjernKommune
     * Endre kommuner direkte på mønstringen, og kall write_monstring::save( $monstring )
     *
     * @param kommune $kommune
     **/
    public function fjernKommune($kommune)
    {
        self::addKommune($kommune);
    }
    /**
     * DEPRECATED: addKontaktperson
     * Endre kontakter direkte på mønstringen, og kall write_monstring::save( $monstring )
     * 
     * @param kontaktperson $kontakt
     **/
    public function addKontaktperson($kontakt)
    {
        die('DEPRECATED: Endre kontaktpersoner direkte på mønstringen, og kall write_monstring::save( $monstring )');
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
     * Faktisk legg til en ny type innslag til mønstringen (db-modifier)
     * 
     * Sjekker at databaseraden ikke allerede eksisterer, og
     * setter inn ny rad ved behov
     *
     * @param Arrangement $monstring
     * @param innslag_type $innslag_type
     * @return bool $sucess
     **/
    public static function _leggTilInnslagtype($monstring_save, $innslag_type)
    {
        try {
            self::controlMonstring($monstring_save);
        } catch (Exception $e) {
            throw new Exception('Kan ikke legge til innslagstype da ' . $e->getMessage());
        }

        $test = new Query(
            "
                SELECT `pl_bt_id`
                FROM `smartukm_rel_pl_bt`
                WHERE `pl_id` = '#pl_id'
                AND `bt_id` = '#bt_id'",
            [
                'pl_id' => $monstring_save->getId(),
                'bt_id' => $innslag_type->getId()
            ]
        );
        $testRes = $test->run('field', 'pl_bt_id');
        if (is_numeric($testRes) && $testRes > 0) {
            return true;
        }

        $insert = new Insert('smartukm_rel_pl_bt');
        $insert->add('pl_id', $monstring_save->getId());
        $insert->add('bt_id', $innslag_type->getId());
        $res = $insert->run();

        if (!$res) {
            return false;
        }

        Logger::log(
            117,
            $monstring_save->getId(),
            $innslag_type->getId()
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
     * @param innslag_type $innslag_type
     * @return bool $sucess
     **/
    public static function _fjernInnslagtype($monstring_save, $innslag_type)
    {
        try {
            self::controlMonstring($monstring_save);
        } catch (Exception $e) {
            throw new Exception('Kan ikke fjerne innslagstype da ' . $e->getMessage());
        }


        $delete = new Delete(
            'smartukm_rel_pl_bt',
            [
                'pl_id' => $monstring_save->getId(),
                'bt_id' => $innslag_type->getId()
            ]
        );
        $res = $delete->run();

        if (in_array($innslag_type->getId(), [8, 9])) {
            $delete2 = new Delete(
                'smartukm_rel_pl_bt',
                [
                    'pl_id' => $monstring_save->getId(),
                    'bt_id' => $innslag_type->getId() == 8 ? 9 : 8
                ]
            );
            $res = $delete2->run();
        }

        if (!$res) {
            return false;
        }

        Logger::log(
            118,
            $monstring_save->getId(),
            $innslag_type->getId()
        );
        return true;
    }

    public static function validateClass( $object ) {
        return is_object( $object ) &&
            in_array( 
                get_class($object),
                ['UKMNorge\Arrangement\Write','write_monstring']
            );
    }
}
