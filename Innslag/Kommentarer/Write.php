<?php

namespace UKMNorge\Innslag\Kommentarer;

use Exception;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Log\Logger;

class Write
{

    /**
     * Opprett (og lagre) en kommentar
     *
     * @param Int $innslag_id
     * @param Int $arrangement_id
     * @param String $kommentar
     * @return Kommentar
     */
    public static function create(Int $innslag_id, Int $arrangement_id, String $kommentar): Kommentar
    {
        $insert = new Insert(Kommentar::TABLE);
        $insert->add('innslag_id', $innslag_id);
        $insert->add('arrangement_id', $arrangement_id);
        $insert->add('kommentar', $kommentar);

        $insert->run();

        return new Kommentar($innslag_id, $arrangement_id, $kommentar);
    }

    /**
     * Lagre en kommentar
     * 
     * Auto-oppretter hvis kommentaren ikke finnes fra før.
     *
     * @param Kommentar $kommentar
     * @throws Exception
     * @return boolean
     */
    public static function save(Kommentar $kommentar)
    {
        static::validerLogger();

        $db_kommentar = Kommentar::getByInnslagId($kommentar->getInnslagId());
        if ($db_kommentar->getKommentar() != $kommentar->getKommentar()) {
            Logger::log(329, $kommentar->getInnslagId(), $kommentar->getKommentar());


            if( $kommentar->eksisterer() ) {
                static::update($kommentar);
            } else {
                Write::save( $kommentar );
            }
        }

        return true;
    }

    /**
     * Slett en kommentar
     *
     * @param Kommentar $kommentar
     * @throws Exception
     * @return boolean
     */
    public static function delete(Kommentar $kommentar): bool
    {
        Logger::log(330, $kommentar->getInnslagId(), '');

        $query = new Delete(
            Kommentar::TABLE,
            [
                'innslag_id' => $kommentar->getInnslagId(),
                'arrangement_id' => $kommentar->getArrangementId()
            ]
        );
        $res = $query->run();

        return true;
    }
    /**
     * Oppdater databasen
     *
     * @param Kommentar $kommentar
     * @throws Exception
     * @return boolean
     */
    private static function update(Kommentar $kommentar): bool
    {
        $query = new Update(
            Kommentar::TABLE,
            [
                'innslag_id' => $kommentar->getInnslagId(),
                'arrangement_id' => $kommentar->getArrangementId()
            ]
        );

        $query->add('kommentar', $kommentar->getKommentar());

        $res = $query->run();

        return true;
    }

    /**
     * Sjekk at loggeren er klar, og gi skikkelig tilbakemelding
     *
     * @throws Exception hvis ikke klar
     */
    public static function validerLogger()
    {
        if (!Logger::ready()) {
            throw new Exception(
                Logger::getError(),
                535001
            );
        }
    }
}
