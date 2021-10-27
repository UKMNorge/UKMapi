<?php

namespace UKMNorge\Filmer\Upload;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Filmer\UKMTV\Tags\Tags;
use UKMNorge\Http\Curl;
use UKMNorge\Innslag\Innslag;

class Uploaded
{

    /**
     * Registrer opplastet film av et innslag
     *
     * @param Int $cronId
     * @param Innslag $innslag
     * @param Arrangement $arrangement
     * @return bool true
     * @throws Exception hvis error
     */
    public static function registrerInnslag(Int $cronId, Innslag $innslag, Arrangement $arrangement)
    {
        $film_tittel = $innslag->getNavn();
        if ($innslag->getType()->harTitler() && $innslag->getTitler()->getAntall() > 0) {
            $tittel = $innslag->getTitler()->getAll()[0]; // her bør opplasteren etter hvert sende med tittel-objektet altså
            $film_tittel .= ' - ' . $tittel->getNavn();
            $beskrivelse = $tittel->getParentes();
        } else {
            $beskrivelse = '';
        }

        $sql = new Insert('ukm_uploaded_video');
        $sql->add('cron_id', $cronId);
        $sql->add('title', $film_tittel);
        $sql->add('description', $beskrivelse);
        $sql->add('arrangement_id', $arrangement->getId());
        $sql->add('innslag_id', $innslag->getId());
        $sql->add('season', $innslag->getSesong());
        $res = $sql->run();

        if (!$res) {
            throw new Exception(
                'Kunne ikke registrere opplasting av film for ' . $innslag->getNavn(),
                515001
            );
        }

        //static::pullRegistrerData($cronId);
        return true;
    }

    /**
     * Registrer opplastet film (som ikke er tilknyttet innslag)
     *
     * @param Int $cronId
     * @param String $tittel
     * @param String $beskrivelse
     * @param Arrangement $arrangement
     * @return Bool
     * @throws Exception hvis error
     */
    public static function registrerReportasje(Int $cronId, String $tittel, String $beskrivelse, Arrangement $arrangement)
    {
        $sql = new Insert('ukm_uploaded_video');
        $sql->add('cron_id', $cronId);
        $sql->add('title', $tittel);
        $sql->add('description', $beskrivelse);
        $sql->add('arrangement_id', $arrangement->getId());
        $sql->add('season', $arrangement->getSesong());
        $res = $sql->run();

        if (!$res) {
            throw new Exception(
                'Kunne ikke registrere opplasting av filmen "' . $tittel . '"',
                515002
            );
        }
        
        //static::pullRegistrerData($cronId);
        return true;
    }

    /**
     * Registrer filmen om den allerede er konvertert
     * 
     * Sender request til videoconverter, som re-sender data-pakken
     * til api.ukm.no, likt som når filmen er konvertert første gang.
     * 
     * Vi curler videoconverter, som curler oss altså. Curling! 🥌
     *
     * @param Int $cronId
     * @return Bool result
     */
    public static function pullRegistrerData(Int $cronId ) {
        $request = new Curl();
        return !!$request->request('https://videoconverter.' . UKM_HOSTNAME . '/api/resend_registration.php?cronId=' . $cronId);
    }
}
