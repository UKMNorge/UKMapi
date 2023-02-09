<?php

namespace UKMNorge\Filmer\UKMTV;

use Exception;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Filmer\UKMTV\CloudflareFilm;


class WriteFilmCloudflare {

    public static $db = 'cloudflare_videos';

    /**
     * Opprett filmen i database eller oppdater det
     *
     * @param Film $film
     */
    public static function createOrUpdate(CloudflareFilm $film) {
        if($film->getId() > -1 && WriteFilmCloudflare::exist($film)) {
            $query = new Update(
                WriteFilmCloudflare::$db,
                [
                    'id' => $film->getId()
                ]
            );
        }
        else {
            $query = new Insert(WriteFilmCloudflare::$db);
        }
        $query = WriteFilmCloudflare::addValuesToQuery($query, $film);
        
        $res = $query->run();
        die($res);

        return $res; // return id
    }

    /**
     * Sjekk om filmen eksisterer
     *
     * @param Film $film
     * @return Bool
     */
    public static function exist(CloudflareFilm $film) {
        $try = new Query(
            "SELECT `id`
            FROM `cloudflare_videos`
            WHERE `id` = '#id'",
            [
                'id' => $film->getId()
            ]
        );

        $id = $try->getField();

        return $id ? true : false;
    }

    /**
     * Slett en film fra UKM-TV
     *
     * @param Film $film
     * @return Bool
     */
    public static function delete(CloudflareFilm $film) {
        $sql = new Update(
            WriteFilmCloudflare::$db,
            [
                'tv_id' => $film->getId()
            ]
        );
        $sql->add('deleted', 'true');
        return $sql->run();
    }


    /**
     * Legg til alle verdier fra film til query
     * Kan brukes for å opprette ny film eller oppdatere det
     *
     * @param Film $film
     * @return Bool
     */
    private static function addValuesToQuery($query, CloudflareFilm $film) {
        $query->add('cloudflare_id', $film->getCloudflareId());
        $query->add('cloudflare_lenke', $film->getUrl());
        $query->add('cloudflare_thumbnail', $film->getThumbnail());
        $query->add('title', $film->getTitle());
        $query->add('description', $film->getDescription());
        $query->add('arrangement_id', $film->getArrangementId());
        $query->add('innslag_id', $film->getInnslagId());
        $query->add('season', $film->getSeason());
        
        return $query;
    }
}