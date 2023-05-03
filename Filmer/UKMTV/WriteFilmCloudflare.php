<?php

namespace UKMNorge\Filmer\UKMTV;

use Exception;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Filmer\UKMTV\CloudflareFilm;
use UKMNorge\Geografi\Kommune;

class WriteFilmCloudflare {

    public static $db = 'cloudflare_videos';

    /**
     * Opprett filmen i database eller oppdater det
     *
     * @param CloudflareFilm $film
     * 
     * @return CloudflareFilm
     */
    public static function createOrUpdate(CloudflareFilm $film) {
        $filmId = WriteFilmCloudflare::exist($film);
        
        if($filmId > 0) {
            $query = new Update(
                WriteFilmCloudflare::$db,
                [
                    'id' => $filmId
                ]
            );
        }
        else {
            $query = new Insert(WriteFilmCloudflare::$db);
        }
        $query = WriteFilmCloudflare::addValuesToQuery($query, $film);
        
        $res = $query->run();

        if($res && $film->getId() == -1) {
            $film->setId($res);
        }

        return $film;
    }

    /**
     * Sjekk om filmen eksisterer
     *
     * @param CloudflareFilm $film
     * @return Int
     */
    public static function exist(CloudflareFilm $film) {
        $try = new Query(
            "SELECT `id`
            FROM `cloudflare_videos`
            WHERE `id` = '#id' or `cloudflare_id` = '#cfId'",
            [
                'id' => $film->getId(),
                'cfId' => $film->getCloudflareId()
            ]
        );

        $id = $try->getField();

        return $id ? $id : -1;
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
     * @return Query
     */
    private static function addValuesToQuery($query, CloudflareFilm $film) {
        $query->add('cloudflare_id', $film->getCloudflareId());
        $query->add('cloudflare_lenke', $film->getUrl());
        $query->add('cloudflare_thumbnail', $film->getThumbnail());
        $query->add('title', $film->getTitle());
        $query->add('description', $film->getDescription());
        $query->add('arrangement', $film->getArrangementId());
        $query->add('innslag', $film->getInnslagId() ? $film->getInnslagId() : null);
        $query->add('sesong', $film->getSesong());
        $query->add('arrangement_type', $film->arrangementType());
        $query->add('fylke', $film->getFylkeId());
        $query->add('deleted', $film->erSlettet() ? 1 : 0);
        $query->add('erReportasje', $film->erReportasje() ? 1 : 0);
        
        return $query;
    }
}