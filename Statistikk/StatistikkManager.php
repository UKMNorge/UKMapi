<?php

namespace UKMNorge\Statistikk;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Nettverk\Administrator;


// Create class StatisikkManager
class StatistikkManager
{
    // Properties

    // Constructor
    public function __construct() {

    }

    /**
     * Check if user has access to arrangement
     * 
     * IMPORTANT: This is a security check to make sure the user has access to the arrangement
     *
     * BYPASS: This check can be bypassed if the user is superadmin
     * 
     * @param int $arrangementId
     * @return bool
     */
    public static function hasAccessToArrangement(int $arrangementId) : bool {
        // Check if user is superadmin
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
     * Check if user has access to minimum 1 kommune
     *
     * @return boolean
     */
    public static function hasKommuneAccess() {
        $user = new Administrator( get_current_user_id() );

        foreach($user->getOmrader() as $omrade) {
            if($omrade->getType() == 'kommune') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user has access to minimum 1 fylke
     *
     * @return boolean
     */
    public static function hasFylkeAccess() {
        $user = new Administrator( get_current_user_id() );

        foreach($user->getOmrader() as $omrade) {
            if($omrade->getType() == 'fylke') {
                return true;
            }
        }

        return false;
    }

}