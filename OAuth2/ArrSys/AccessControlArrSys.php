<?php

namespace UKMNorge\OAuth2\ArrSys;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Nettverk\Administrator;
use UKMNorge\Nettverk\Omrade;



class AccessControlArrSys {

    // Constructor
    public function __construct() {

    }

    /**
     * Security check to make sure the user has access to a specific område
     * 
     * HUSK: Fylke admin har tilgang til alle kommuner i fylket
     *
     * @return boolean
     */
    public static function hasOmradeAccess(Omrade $omrade) {
        if(is_super_admin()) {
            return true;
        }

        if($omrade == null) {
            return false;
        }

        if($omrade->getType() == 'fylke') {
            return self::hasAccessToFylke($omrade->getForeignId());
        }
        else if($omrade->getType() == 'kommune') {
            // Fylke admin har tilgang til alle kommuner i fylket. Hvis området er en kommune, sjekk om brukeren har tilgang til fylket
            if($omrade->getFylke()) {
                return self::hasAccessToFylke($omrade->getFylke()->getId());
            }
            return self::hasAccessToKommune($omrade->getForeignId());
        }
        // arrangement, monstring og land er alle Arrangement (klasse) type
        else if($omrade->getType() == 'arrangement' || $omrade->getType() == 'monstring' || $omrade->getType() == 'land') {
            return self::hasAccessToArrangement($omrade->getForeignId());
        }

        return false;
    }


    /**
     * Security check to make sure the user has access minimum 1 arrangement (arrangement level)
     *
     * @return boolean
     */
    public static function hasArrangementAccess() {
        if(is_super_admin()) {
            return true;
        }
        
        // Check if user has access to arrangement
        $blogs = get_blogs_of_user(get_current_user_id());

        foreach($blogs as $blog) {
            $blog_id = $blog->userblog_id; // Get the blog ID
            switch_to_blog($blog_id); // Switch to the context of the current blog

            // Arrangement
            if (get_option('pl_id') !== false) {
                return true;
            }

            restore_current_blog();
        }

        return false;
    }

    /**
     * 
     * Security check to make sure the user has access to a specific arrangement
     *
     * 
     * @param int $arrangementId
     * @return boolean
     */
    public static function hasAccessToArrangement(int $arrangementId) : bool {
        if(is_super_admin()) {
            return true;
        }

        // Check if user has access to arrangement
        $blogs = get_blogs_of_user(get_current_user_id());

        foreach($blogs as $blog) {
            $blog_id = $blog->userblog_id; // Get the blog ID
            switch_to_blog($blog_id); // Switch to the context of the current blog

            // Arrangement
            if (get_option('pl_id') !== false) {
                // Check if blog has pl_id
                $pl_id = get_option('pl_id');

                if ($pl_id && $pl_id == $arrangementId) {
                    return true;
                }
            }

            restore_current_blog();
        }

        return false;
    }

    /**
     * Security check to make sure the user has access to a specific kommune
     *
     * @return boolean
     */
    public static function hasKommuneAccess() {
        if(is_super_admin()) {
            return true;
        }

        $user = new Administrator( get_current_user_id() );

        foreach($user->getOmrader() as $omrade) {
            if($omrade->getType() == 'kommune') {
                return true;
            }
        }

        return false;
    }


    /**
     * Security check to make sure the user has access minimum 1 kommune (kommune level)
     *
     * @return boolean
     */
    public static function hasAccessToKommune(int $kommuneId) {
        if(is_super_admin()) {
            return true;
        }

        $user = new Administrator( get_current_user_id() );
        
        foreach($user->getOmrader() as $omrade) {
            if($omrade->getType() == 'kommune' && $omrade->getForeignId() == $kommuneId) {
                return true;
            }
        }

        return false;
    }

     /**
     * Security check to make sure the user is administrator in a kommune that is part of the fylke
     *
     * @return boolean
     */
    public static function hasAccessToFylkeFromKommune(int $fylkeId) {
        if(is_super_admin()) {
            return true;
        }

        $user = new Administrator( get_current_user_id() );
        
        foreach($user->getOmrader() as $omrade) {
            if($omrade->getType() == 'kommune' && $omrade->getFylke() && $omrade->getFylke()->getId() == $fylkeId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Security check to make sure the user has access minimum 1 fylke (fylke level)
     *
     * @return boolean
     */
    public static function hasFylkeAccess() {
        if(is_super_admin()) {
            return true;
        }

        $user = new Administrator( get_current_user_id() );

        foreach($user->getOmrader() as $omrade) {
            if($omrade->getType() == 'fylke') {
                return true;
            }
        }

        return false;
    }

    /**
     * Security check to make sure the user has access to a specific fylke
     *
     * @return void
     */
    public static function hasAccessToFylke(int $fylkeId) {
        if(is_super_admin()) {
            return true;
        }

        $user = new Administrator( get_current_user_id() );
        
        foreach($user->getOmrader() as $omrade) {
            if($omrade->getType() == 'fylke' && $omrade->getForeignId() == $fylkeId) {
                return true;
            }
        }

        return false;
    }
}