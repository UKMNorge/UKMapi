<?php

namespace UKMNorge\SearchArrangorsystemet;
use UKMNorge\Nettverk\Administrator;

use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class Search {
    // Brukere kan søke på arrangementer, innslag, blogginnlegg, og brukere
    public static function sokBlogs(String $searchInput) {
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
    
    public static function searchOmraader($searchInput) {
        $retOmrader = [];

        $current_admin = new Administrator(get_current_user_id());
        $omrader = $current_admin->getOmrader();
        foreach($omrader as $omrade) {
            // Alle kommuner i fylket
            if($omrade->getType() == 'fylke') {
                foreach($omrade->getFylke()->getKommuner() as $kommune) {
                    if(static::searchBasedOnText($kommune->getNavn(), $searchInput)) {
                        $retOmrader[] = [
                            'id' => $kommune->getId(),
                            'navn' => $kommune->getNavn(),
                            'type' => 'kommune',
                            'siteUrl' => $kommune->getLink(),
                        ];
                    }
                }
            }
            if(static::searchBasedOnText($omrade->getNavn(), $searchInput)) {
                $retOmrader[] = [
                    'id' => $omrade->getForeignId(),
                    'navn' => $omrade->getNavn(),
                    'type' => $omrade->getType(),
                    'siteUrl' => $omrade->getLink(),
                ];
            }
        }

        return $retOmrader;
    }

    public function sokDeltakere() {
    
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

