<?php

namespace UKMNorge\Filmer\Upload;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Filmer\Server\Server;
use UKMNorge\Filmer\Write;
use UKMNorge\Innslag\Innslag;

class Converted {

    public static function registerReportasje(){
        Publish::reportasje();
    }

    public static function registerInnslag(
        Innslag $innslag,
        Arrangement $arrangement,
        Int $cronId,
        Int $blogId,
        String $blogUrl,
        String $storage_path,
        String $storage_filename
    ) {
        error_log('Converted:registerInnslag()');
        error_log('- innslagId: '. $innslag->getId());
        error_log('- cronId: '. $cronId);
        error_log('- blogId: ', $blogId);
        error_log('- storage_path: ', $storage_path);
        error_log('- storage_filename: ', $storage_filename);

        // Oppdater ukmno_wp_related-tabellen
        error_log('Converted::updateWpRelated');
        $resWpRelated = Related::updateWpRelated(
            $cronId,
            $blogId,
            $blogUrl,
            $innslag,
            $arrangement,
            $storage_path,
            $storage_filename
        );

        // Oppdater ukm_related-tabellen
        error_log('Converted::updateUKMRelated');
        $resUkmRelated = Related::updateUkmRelated(
            $cronId,
            $storage_path,
            $storage_filename
        );

        // Registrer filmen i UKM-TV
        Write::createFraRelatedTabell($cronId);
        Write::oppdaterFraRelatedTabell( $cronId );

        Publish::innslag();
    }

    /**
     * Beregn full path til filen på lagringsserveren
     *
     * @param String $storage_path
     * @param String $storage_filename
     * @return String full path
     */
    public static function getFileWithPath( String $storage_path, String $storage_filename ) {
        return Server::STORAGE_BASEPATH . rtrim( $storage_path, '/') . '/' . $storage_filename;
    }
}