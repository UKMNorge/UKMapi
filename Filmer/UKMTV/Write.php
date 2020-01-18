<?php

namespace UKMNorge\Filmer\UKMTV;

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

    public static function save( Film $film ) {

        $db_film = Filmer::getById( $film->getId() );

        $properties = [
            'cron_id' => 'cronId',
            'pl_id' => 'arrangementId',
            'tv_title' => ''
        ]

        foreach( $properties as $db_field => $value ) {
            $propFunct = 'get'.ucfirst($property);
            if( $db_film->$propFunct() != $)
        }
    }
    
    public static function oppdater(array $data)
    { }

    public static function opprett(
        String $title,
        String $description,
        String $file,
        String $image,
        Int $season,
    ) {

        
        return Filmer::getById( $insert_id );
    }
}
