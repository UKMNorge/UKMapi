<?php

namespace UKMNorge\Innslag\Nominasjon;

use UKMNorge\Database\SQL\Insert;
use UKMNorge\Innslag\Innslag;
use UKMNorge\Log\Logger;
use Exception;
use ReflectionClass;
use UKMNorge\Database\SQL\Update;

class Write
{
    /**
     * Opprett en ny nominasjon
     *
     * @param Innslag $innslag
     * @param Int $fra_arrangement_id
     * @param Int $til_arrangement_id
     * @return Nominasjon
     */
    public static function create(Innslag $innslag, Int $fra_arrangement_id, Int $til_arrangement_id)
    {
        static::requireLogger();

        $innslag_type = $innslag->getType()->getKey() == 'nettredaksjon' ? 'media' : $innslag->getType()->getKey();
        $classname = 'UKMNorge\Innslag\Nominasjon\\' . ucfirst($innslag_type);
        $obj = $classname::getByInnslag($innslag, $fra_arrangement_id, $til_arrangement_id);

        if (!$obj->harNominasjon()) {
            $sql = new Insert('ukm_nominasjon');
            #$sql->charset('utf8');
            $sql->add('b_id', $innslag->getId());
            $sql->add('season', $innslag->getSesong());
            $sql->add('arrangement_fra', $fra_arrangement_id);
            $sql->add('arrangement_til', $til_arrangement_id);
            $sql->add('type', $innslag_type);
            $insert_id = $sql->run();

            self::log($sql->debug());

            if (!$insert_id) {
                throw new Exception(
                    'Kunne ikke opprette nominasjon!',
                    522003
                );
            }

            $sql2 = new Insert('ukm_nominasjon_' . $innslag_type);
            $sql2->add('nominasjon', $insert_id);
            $res2 = $sql2->run();
            self::log($sql2->debug());

            if (!$res2) {
                throw new Exception(
                    'Kunne ikke opprette nominasjon (opprettelse detaljrad feilet)',
                    522004
                );
            }

            $obj = $classname::getByInnslag($innslag, $fra_arrangement_id, $til_arrangement_id);

            if (!$obj) {
                throw new Exception(
                    'Noe feilet ved opprettelsen av nominasjonen',
                    522005
                );
            }
        }

        return $obj;
    }

    /**
     * Lagre hvorvidt et innslag er nominert eller ikke
     *
     * @param Nominasjon $nominasjon
     * @param Bool $state
     * @return Bool
     */
    public static function saveState(Nominasjon $nominasjon, Bool $state)
    {
        static::requireNominasjon($nominasjon);

        $sql = new Update(
            'ukm_nominasjon',
            [
                'id' => $nominasjon->getId(),
            ]
        );
        $sql->add('nominert', $state ? 'true' : 'false');
        $sql->run();

        self::log($sql->debug());

        return true;
    }

    /**
     * Oppprett en voksen
     *
     * @param Int $nominasjon_id
     * @return Voksen
     */
    public static function createVoksen(Int $nominasjon_id)
    {
        static::requireLogger();

        try {
            $obj = new Voksen($nominasjon_id);
        } catch (Exception $e) {
            $sql = new Insert('ukm_nominasjon_voksen');
            $sql->add('nominasjon', $nominasjon_id);
            $res = $sql->run();

            self::log($sql->debug());

            if (!$res) {
                throw new Exception(
                    'Kunne ikke opprette voksen!',
                    522005
                );
            }

            $obj = new Voksen($nominasjon_id);
        }
        return $obj;
    }

    /**
     * Lagre endringer i voksen
     *
     * @param Voksen $voksen
     * @return void
     */
    public static function saveVoksen(Voksen $voksen)
    {
        $sql = new Update(
            'ukm_nominasjon_voksen',
            [
                'nominasjon' => $voksen->getNominasjon()
            ]
        );
        #$sql->charset('utf8');
        $sql->add('navn', $voksen->getNavn());
        $sql->add('mobil', $voksen->getMobil());
        $sql->add('rolle', $voksen->getRolle());
        $res = $sql->run();

        self::log($sql->debug());

        return true;
    }

    /**
     * Lagre endringer i media-skjema
     *
     * @param Media $nominasjon
     * @return void
     */
    public static function saveMedia(Media $nominasjon)
    {
        static::requireNominasjon($nominasjon);

        $sql = new Update(
            'ukm_nominasjon_media',
            [
                'nominasjon' => $nominasjon->getId()
            ]
        );
        $sql->add('pri_1', $nominasjon->getPri1());
        $sql->add('pri_2', $nominasjon->getPri2());
        $sql->add('pri_3', $nominasjon->getPri3());
        $sql->add('annet', $nominasjon->getAnnet());
        $sql->add('beskrivelse', $nominasjon->getBeskrivelse());
        $sql->add('samarbeid', $nominasjon->getSamarbeid());
        $sql->add('erfaring', $nominasjon->getErfaring());
        $res = $sql->run();

        self::log($sql->debug());
    }

    /**
     * Lagre endringer i konferansier-skjema
     *
     * @param Konferansier $nominasjon
     * @return void
     */
    public static function saveKonferansier(Konferansier $nominasjon)
    {
        static::requireNominasjon($nominasjon);

        $sql = new Update(
            'ukm_nominasjon_konferansier',
            [
                'nominasjon' => $nominasjon->getId()
            ]
        );
        $sql->add('hvorfor', $nominasjon->getHvorfor());
        $sql->add('beskrivelse', $nominasjon->getBeskrivelse());
        $sql->add('fil-plassering', $nominasjon->getFilPlassering());
        $sql->add('fil-url', $nominasjon->getFilUrl());
        $res = $sql->run();

        self::log($sql->debug());
    }

    /**
     * Lagre endringer i arrangør-skjema
     *
     * @param Arrangor $nominasjon
     * @return void
     */
    public static function saveArrangor(Arrangor $nominasjon)
    {
        static::requireNominasjon($nominasjon);

        $sql = new Update(
            'ukm_nominasjon_arrangor',
            [
                'nominasjon' => $nominasjon->getId()
            ]
        );
        $sql->add('type_lydtekniker', $nominasjon->getLydtekniker() ? 'true' : 'false');
        $sql->add('type_lystekniker', $nominasjon->getLystekniker() ? 'true' : 'false');
        $sql->add('type_vertskap', $nominasjon->getVertskap() ? 'true' : 'false');
        $sql->add('type_produsent', $nominasjon->getProdusent() ? 'true' : 'false');

        $sql->add('samarbeid', $nominasjon->getSamarbeid());
        $sql->add('erfaring', $nominasjon->getErfaring());
        $sql->add('suksesskriterie', $nominasjon->getSuksesskriterie());
        $sql->add('annet', $nominasjon->getAnnet());

        $sql->add('lyd-erfaring-1', $nominasjon->getLydErfaring1());
        $sql->add('lyd-erfaring-2', $nominasjon->getLydErfaring2());
        $sql->add('lyd-erfaring-3', $nominasjon->getLydErfaring3());
        $sql->add('lyd-erfaring-4', $nominasjon->getLydErfaring4());
        $sql->add('lyd-erfaring-5', $nominasjon->getLydErfaring5());
        $sql->add('lyd-erfaring-6', $nominasjon->getLydErfaring6());

        $sql->add('lys-erfaring-1', $nominasjon->getLysErfaring1());
        $sql->add('lys-erfaring-2', $nominasjon->getLysErfaring2());
        $sql->add('lys-erfaring-3', $nominasjon->getLysErfaring3());
        $sql->add('lys-erfaring-4', $nominasjon->getLysErfaring4());
        $sql->add('lys-erfaring-5', $nominasjon->getLysErfaring5());
        $sql->add('lys-erfaring-6', $nominasjon->getLysErfaring6());

        $sql->add('voksen-samarbeid', $nominasjon->getVoksenSamarbeid());
        $sql->add('voksen-erfaring', $nominasjon->getVoksenErfaring());
        $sql->add('voksen-annet', $nominasjon->getVoksenAnnet());

        $res = $sql->run();

        self::log($sql->debug());
    }

    /**
     * Lagre sorry-state, altså at man må være med på hele
     *
     * @param Nominasjon $nominasjon
     * @param String $flagg
     * @return Bool
     */
    public static function saveSorry(Nominasjon $nominasjon, String $flagg)
    {
        static::requireNominasjon($nominasjon);

        $sql = new Update(
            'ukm_nominasjon_arrangor',
            [
                'nominasjon' => $nominasjon->getId(),
            ]
        );
        $sql->add('sorry', $flagg);
        $sql->run();

        self::log($sql->debug());
        return true;
    }

    /**
     * Lagre en hvilken som helst nominasjon
     *
     * @param Nominasjon $nominasjon
     * @return void
     */
    public static function save(Nominasjon $nominasjon) {
        static::requireLogger();
        
        $nominasjon->calcHarSkjemaStatus();


        $classname = (new ReflectionClass($nominasjon))->getShortName();
        $save = 'save'.ucfirst($classname);
        return static::$save($nominasjon);
    }


    /**
     * Lagre logg av databasespørringene som utføres
     *
     * @param String $string
     * @return void
     */
    public static function log(String $string)
    {
        if (!isset($_ENV['HOME'])) {
            $_ENV['HOME'] = sys_get_temp_dir();
        }
        error_log($string);
        ini_set("error_log", $_ENV['HOME'] . "/logs/error_log_write_nominasjon.log");
        error_log($string);
    }

    /**
     * Sjekk at vi har et gyldig nominasjon-objekt med databaserad å
     *
     * @param Nominasjon $nominasjon
     * @throws Exception
     * @return void
     */
    public static function requireNominasjon(Nominasjon $nominasjon)
    {
        static::requireLogger();

        if (!$nominasjon->eksisterer()) {
            throw new Exception(
                'Lagring av nominasjon-detaljer krever numerisk id',
                522002
            );
        }
    }

    /**
     * Sjekk at loggeren er riktig satt opp
     *
     * @return void
     * @throws Exception
     */
    public static function requireLogger()
    {
        if (!Logger::ready()) {
            throw new Exception(
                'Logger is missing or incorrect set up.',
                522001
            );
        }
    }
}
