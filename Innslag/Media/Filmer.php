<?php

namespace UKMNorge\Innslag\Media;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Filmer\Film;
use UKMNorge\Filmer\Filmer as UKMNorgeFilmer;

class Filmer extends UKMNorgeFilmer {
    
    /**
     * Opprett en filmerCollection for gitt innslagId
     *
     * @param Int $innslagId
     * @return Filmer
     */
    public static function getByInnslag(Int $innslagId)
    {
        $query = new Query(
            Film::getLoadQuery() ."
            WHERE `file`.`b_id` = '#innslagId'
            AND `tv_deleted` = 'false'", // deleted ikke nødvendig, men gjør lasting marginalt raskere
            [
                'innslagId' => $innslagId
            ]
        );
        return new Filmer($query);
    }
}