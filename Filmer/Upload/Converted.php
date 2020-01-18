<?php

namespace UKMNorge\Filmer\Upload;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Filmer\UKMTV\FilmInterface;
use UKMNorge\Filmer\UKMTV\Server\Server;
use UKMNorge\Filmer\UKMTV\Write;
use UKMNorge\Innslag\Innslag;
use UKMNorge\Filmer\Upload\Film;

class Converted
{
    /**
     * Registrer en reportasje-film
     *
     * @param Int $cronId
     * @param Arrangement $arrangement
     * @param String $storage_path
     * @param String $storage_filename
     * @return Film 
     */
    public static function registerReportasje(
        Int $cronId,
        Arrangement $arrangement,
        String $storage_path,
        String $storage_filename
    ) {
        static::setAndSaveFilePath(
            $cronId,
            static::getFileWithPath($storage_path, $storage_filename)
        );
        $film = new Film($cronId);
        $film->setTags( Tags::getForReportasje( $arrangement ));
        return $film;
    }

    /**
     * Registrer film av et innslag
     *
     * @param Int $cronId
     * @param Arrangement $arrangement
     * @param String $storage_path
     * @param String $storage_filename
     * @param Innslag $innslag
     * @return Film
     */
    public static function registerInnslag(
        Int $cronId,
        Arrangement $arrangement,
        String $storage_path,
        String $storage_filename,
        Innslag $innslag
    ) {
        static::setAndSaveFilePath(
            $cronId,
            static::getFileWithPath($storage_path, $storage_filename)
        );

        $film = new Film($cronId);
        $film->setTags( Tags::getForInnslag( $arrangement, $innslag ));
        return $film;
    }

    /**
     * Registrer eller oppdater en film i UKM-TV basert på gitt film-objekt
     *
     * @param FilmInterface $film
     * @return Int $tv_id
     */
    public static function sendToUKMTV(FilmInterface $film)
    {
        Write::import($film); // Setter TV-ID på objektet
        static::_saveTvId($film); // Lagrer TV-ID i ukm_uploaded_video
        return $film->getTvId();
    }

    /**
     * Lagre TV-ID i ukm_uploaded_video
     *
     * @param FilmInterface $film
     * @throws Exception
     * @return bool
     */
    private static function _saveTvId(FilmInterface $film)
    {
        if (empty($film->getTvId())) {
            throw new Exception(
                'Beklager, kan ikke lagre TV-id uten at det er satt på gitt film-objekt',
                515005
            );
        }

        $update = new Update(
            'ukm_uploaded_video',
            [
                'cron_id' => $film->getCronId(),
            ]
        );
        $update->add('tv_id', $film->getTvId());

        $res = $update->run();
        if (!$res && $res !== 0) {
            throw new Exception(
                'Beklager, en feil oppsto når vi prøvde å knytte ' .
                'cronId::' . $film->getCronId() . ' til UKM-TV::' . $film->getTvId(),
                515006
            );
        }
        return true;
    }

    /**
     * Beregn full path til filen på lagringsserveren
     *
     * @param String $storage_path
     * @param String $storage_filename
     * @return String full path
     */
    public static function getFileWithPath(String $storage_path, String $storage_filename)
    {
        return Server::STORAGE_BASEPATH . 
            str_replace(
                Server::STORAGE_BASEPATH,
                '',
                rtrim($storage_path, '/')
            ) . 
            '/' . $storage_filename;
    }

    /**
     * Oppdater fil-feltet i ukm_uploaded_video
     *
     * @param Int $cronId
     * @param String $fullPath
     * @return bool true
     * @throws Exception
     */
    public static function setAndSaveFilePath(Int $cronId, String $fullPath)
    {
        $query = new Update(
            'ukm_uploaded_video',
            ['cron_id' => $cronId]
        );
        $query->add('file', $fullPath);
        $query->add('converted','true');
        $res = $query->run();
        if ( !$res && $res !== 0) {
            throw new Exception(
                'Kunne ikke oppdatere fil-parameter for cron ' . $cronId,
                515003
            );
        }
        return true;
    }
}
