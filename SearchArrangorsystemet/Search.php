<?php

namespace UKMNorge\SearchArrangorsystemet;
use UKMNorge\Nettverk\Administrator;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\SearchArrangorsystemet\ClientObject;

use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class Search {
    // Brukere kan søke på arrangementer, innslag, blogginnlegg, og brukere
    public static function sokBlogs(string $searchInput) {
        $blogs = get_blogs_of_user(get_current_user_id());
        $retBlogs = [];
        // Search for blogname
        foreach ($blogs as $blog) {
            if(static::searchBasedOnText($blog->blogname, $searchInput)) {
                $blog_id = $blog->userblog_id; // Get the blog ID
                switch_to_blog($blog_id); // Switch to the context of the current blog
                
                // Henter kun arrangementer herfra
                if(get_option('site_type') == 'arrangement') {
                    $retBlogs[] = [
                        'site_id' => $blog->site_id,
                        'userblog_id' => $blog->userblog_id,
                        'title' => $blog->blogname,
                        'siteUrl' => $blog->siteurl,
                        'site_type' => get_option('site_type'),
                        'pl_id' => get_option('pl_id'),
                        'kommune' => get_option('kommune'),
                        'season' => get_option('season'),
                    ];
                }

                restore_current_blog();
            }
        }   

        return $retBlogs;
    }

    public static function searchLog(string $searchInput, string $contextId) : int {
        return Write::createSearchLog($searchInput, $contextId, get_current_user_id());
    }

    public static function clickedResult($logId, $resultId, $text=null) : void {
        if($logId == null || $logId == -1) {
            return;
        }
        Write::clickedResult($logId, $resultId, $text);
    }
    
    public static function searchOmraader($searchInput) : array {
        $retOmrader = [];

        $current_admin = new Administrator(get_current_user_id());
        $omrader = $current_admin->getOmrader();
        foreach($omrader as $omrade) {
            // Søk alle kommuner hvis område er fylket
            if($omrade->getType() == 'fylke') {
                foreach($omrade->getFylke()->getKommuner() as $kommune) {
                    if(static::searchBasedOnText($kommune->getNavn(), $searchInput)) {
                        $cObject = new ClientObject($kommune->getId(), $kommune->getNavn(), 'kommune', $kommune->getLink());
                        $retOmrader[] = $cObject->toArray();
                    }
                }
            }
            if(static::searchBasedOnText($omrade->getNavn(), $searchInput)) {
                $cObject = new ClientObject($omrade->getId(), $omrade->getNavn(), $omrade->getType(), $omrade->getLink());
                $retOmrader[] = $cObject->toArray();
            }
        }

        return $retOmrader;
    }

    public static function sokDeltakere(Arrangement $arrangement, string $searchInput) : array{
        $retObjs = [];

        foreach($arrangement->getInnslag()->getAll() as $innslag) {
            foreach($innslag->getPersoner()->getAll() as $person) {
                if( $person->getMobil() == $searchInput ||
                    $person->getEpost() == $searchInput ||
                    $person->getRolle() == $searchInput ||
                    static::searchBasedOnText($person->getNavn(), $searchInput)) {
                        $cObject = new ClientObject($person->getId(), $person->getNavn(), 'person', $arrangement->getLink().'/wp-admin/admin.php?page=UKMdeltakere&openInnslag='.$innslag->getId(), 'Innslag: '.$innslag->getNavn());
                        $retObjs[] = $cObject->toArray();
                }
            }
        }
        return $retObjs;
    }

    public static function sokInnslag(Arrangement $arrangement, string $searchInput) : array {
        $retObjs = [];
        foreach($arrangement->getInnslag()->getAll() as $innslag) {
            if(static::searchBasedOnText($innslag->getNavn(), $searchInput)) {
                $cObject = new ClientObject($innslag->getId(), $innslag->getNavn(), 'innslag', $arrangement->getLink().'/wp-admin/admin.php?page=UKMdeltakere&openInnslag='.$innslag->getId());
                $retObjs[] = $cObject->toArray();
            }
        }

        return $retObjs;
    }

    private static function searchBasedOnText($entitytext, $searchInput) : bool {
        $searchThreshold = 3;
        $distance = levenshtein($entitytext, $searchInput);

        if($distance < $searchThreshold) {
            return true;
        }

        // Check for exact string position
        return strpos(strtolower($entitytext), strtolower($searchInput)) !== false;
    }
}

