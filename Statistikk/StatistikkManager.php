<?php

namespace UKMNorge\Statistikk;

use UKMNorge\Arrangement\Arrangement;

// Create class StatisikkManager
class StatistikkManager
{
    // Properties

    // Constructor
    public function __construct() {

    }

    public static function hasAccessToArrangement(int $arrangementId) : bool {
        // Check if user has access to arrangement
        $blogs = get_blogs_of_user(get_current_user_id());

        foreach($blogs as $blog) {
            $blog_id = $blog->userblog_id; // Get the blog ID
            switch_to_blog($blog_id); // Switch to the context of the current blog

            if(get_option('site_type') == 'arrangement') {
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
}