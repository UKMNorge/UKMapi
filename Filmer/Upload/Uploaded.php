<?php

namespace UKMNorge\Filmer\Upload;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Filmer\Tags\Tags;
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
    public static function innslag(Int $cronId, Innslag $innslag, Arrangement $arrangement)
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
        $sql->add('title', $tittel);
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
    public static function reportasje(Int $cronId, String $tittel, String $beskrivelse, Arrangement $arrangement)
    {
        $sql = new SQLins('ukm_uploaded_video');
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

        return true;
    }
}
