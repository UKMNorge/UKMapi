<?php

namespace UKMNorge\Filmer;

use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;

class Write
{

    /**
     * Slett en film fra UKM-TV
     *
     * @param Film $film
     * @return Bool
     */
    public static function slett(Film $film)
    {
        $sql = new Update(
            'ukm_tv_files',
            [
                'tv_id' => $film->getId()
            ]
        );
        $sql->add('tv_deleted', 'true');
        return $sql->run();
    }

    public static function oppdater(array $data)
    { }

    public static function create()
    { }


    
}
