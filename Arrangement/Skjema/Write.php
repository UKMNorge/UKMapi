<?php

namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Eier;
use UKMNorge\Database\SQL\Insert;
use Exception;

require_once('UKM/Arrangement/Arrangement.php');
require_once('UKM/Arrangement/Skjema/Skjema.php');
require_once('UKM/Arrangement/Skjema/Sporsmal.php');
require_once('UKM/Arrangement/Eier.php');
require_once('UKM/Database/SQL/insert.class.php');

class Write {
    /**
     * Opprett et skjema
     * Lagres automatisk i databasen
     *
     * @param Arrangement $arrangement
     * @param Eier $eier
     * @return Skjema $skjema
     * @throws Exception hvis database-persist feiler
     */
    public static function create( Arrangement $arrangement ) {
        $insert = new Insert('ukm_videresending_skjema');
        $insert->add('pl_id', $arrangement->getId());
        $insert->add('eier_type', $arrangement->getEierObjekt()->getType());
        $insert->add('eier_id', $arrangement->getEierObjekt()->getId());

        $res = $insert->run();

        if( !$res ) {
            throw new Exception(
                'Kunne ikke opprette spørreskjema. '.
                'Systemet sa '. $res->getError(),
                551001
            );
        }

        return new Skjema(
            $res,
            $arrangement->getId(),
            $arrangement->getEierObjekt()->getType(),
            $arrangement->getEierObjekt()->getId()
        );
    }

    /**
     * Opprett et nytt spørsmål
     *
     * @param Skjema $skjema
     * @param Int $rekkefolge
     * @param String $type
     * @param String $tittel
     * @param String $tekst
     * @return Spørsmål $sporsmal
     */
    public static function createSporsmal( Skjema $skjema, Int $rekkefolge, String $type, String $tittel, String $tekst) {
        $insert = new Insert('ukm_videresending_skjema_sporsmal');
        $insert->add('skjema', $skjema->getId());
        $insert->add('rekkefolge', $rekkefolge);
        $insert->add('type', $type);
        $insert->add('tittel', $tittel);
        $insert->add('tekst', $tekst);

        $res = $insert->run();
        if( !$res ) {
            throw new Exception(
                'Kunne ikke opprette spørsmål '. $tittel .'. '.
                'Systemet sa '. $res->getError(),
                551002
            );
        }
        return new Sporsmal(
            $res, 
            $skjema->getId(),
            $rekkefolge,
            $type,
            $tittel,
            $tekst
        );
    }

    /**
     * Lagre et eksisterene spørsmål
     *
     * @param Sporsmal $sporsmal
     * @return Sporsmal $sporsmal
     */
    public static function saveSporsmal( Sporsmal $sporsmal ) {
        if( !is_numeric($sporsmal->getId()) || $sporsmal->getId() == 0) {
            throw new Exception(
                'Opprett spørsmål før du kjører lagre på de. @see ::saveSporsmal',
                551003
            );
        }
        $query = new Insert(
            'ukm_videresending_skjema_sporsmal',
            [
                'id' => $sporsmal->getId()
            ]  
        );
        $query->add('rekkefolge', $sporsmal->getRekkefolge());
        $query->add('type', $sporsmal->getType());
        $query->add('tittel', $sporsmal->getTittel());
        $query->add('tekst', $sporsmal->getTekst());

        $res = $query->run();

        if( $res === false ) {
            throw new Exception(
                'Kunne ikke lagre spørsmål '. $sporsmal->getTittel() .'. '.
                'Systemet sa: '. $res->getError(),
                551004
            );
        }

        return $sporsmal;
    }
}