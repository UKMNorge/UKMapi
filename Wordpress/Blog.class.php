<?php

namespace UKMNorge\Wordpress;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use innslag_type;

class Blog
{

    /**
     * @TODO
     * PORT: write_wp_blog::avlys, splitt, flytt, flippBlogger, moveBlog
     */

    /**
     * Er gitt path tilgjengelig for blogg-opprettelse?
     *
     * @param String $path
     * @return boolean
     */
    public static function isAvailablePath(String $path)
    {
        try {
            static::getIdByPath($path);
            return false;
        } catch (Exception $e) {
            return true;
        }
    }

    /**
     * Finnes blogg på gitt path?
     *
     * @param String $path
     * @return bool
     */
    public static function eksisterer(String $path)
    {
        return !static::isAvailablePath($path);
    }

    /**
     * Finn ID for en blogg fra gitt path
     *
     * @param String $path
     * @return Int $id
     */
    public static function getIdByPath(String $path)
    {
        $path = static::controlPath($path);
        $result = domain_exists(UKM_HOSTNAME, $path, 1);

        if( !is_numeric($result) ) {
            throw new Exception(
                'Finner ingen blogg på ' . $path,
                172007
            );
        }
        return (int) $result;
    }

    /**
     * Sikre at path er innenfor vår standard
     *
     * @param String $path
     * @return String $path
     */
    public static function sanitizePath(String $path)
    {
        return preg_replace(
            "/[^a-z0-9_]/",
            '',
            str_replace(
                ['æ', 'ø', 'å', 'ü', 'é', 'è'],
                ['a', 'o', 'a', 'u', 'e', 'e'],
                mb_strtolower($path)
            )
        );
    }

    /**
     * Opprett en blogg for et arrangement
     *
     * @param Arrangement $arrangement
     * @param String $path
     * @return Int $blog_id
     */
    public static function opprettForArrangement(Arrangement $arrangement, String $path)
    {

        $path = static::controlPath($path);
        if (static::eksisterer($path)) {
            throw new Exception(
                'Kunne ikke opprette blogg da mønstringen allerede eksisterer',
                172008
            );
        }

        // Opprett tom blogg, og sjekk at vi får gyldig blogg-id tilbake
        $blog_id = wp_insert_site(
            [
                'domain' => UKM_HOSTNAME,
                'path' => $path,
                'network_id' => 1,
                'user_id' => 1,
                'title' => $arrangement->getNavn()
            ]
        );
        if( is_numeric( $blog_id ) ) {
            $blog_id = (Int) $blog_id;
        }
        static::controlBlogId($blog_id);
        static::oppdaterFraArrangement($blog_id, $arrangement);

        return $blog_id;
    }

    /**
     * Oppdater en blogg til å stemme med standardinnhold og -innstillinger
     *
     * @param Int $blog_id
     * @param Arrangement $arrangement
     * @return void
     */
    public static function oppdaterFraArrangement(Int $blog_id, Arrangement $arrangement)
    {
        static::controlBlogId($blog_id);
        // Fellesinnstillinger for alle blogger (tema, frontpage osv)
        static::setStandardInnstillinger($blog_id);
        static::setArrangementData($blog_id, $arrangement);
        static::setStandardInnhold($blog_id, $arrangement->getType());
    }

    /**
     * Sjekk at path faktisk er string før vi starter behandling
     *
     * @param String $path
     * @return String /$path
     * @throws Exception hvis ugyldig path gitt
     */
    public static function controlPath(String $path)
    {
        if (!is_string($path)) {
            throw new Exception('Gitt path er ikke en string');
        }
        if (empty($path)) {
            throw new Exception('Gitt path er tom');
        }
        // WP slenger selv på trailing slash
        if (strpos($path, '/') === false) {
            $path = '/' . $path;
        }
        return $path;
    }


    /**
     * Legg til flere brukere til blogg
     *
     * @param Int $blog_id
     * @param Array $users [id, role]
     * @return Array $rapport
     */
    public function leggTilBrukere(Int $blog_id, array $users)
    {
        static::controlBlogId($blog_id);
        $rapport = [];
        foreach ($users as $user) {
            try {
                static::controlUserData($user);
                static::leggTilBruker($blog_id, $user->id, $user->role);
                $rapport[] = [
                    'success' => true,
                    'user' => $user
                ];
            } catch (Exception $e) {
                $rapport[] = [
                    'success' => false,
                    'user' => $user,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ];
            }
        }
        return $rapport;
    }

    /**
     * Legg til bruker til blogg
     *
     * @param Int $blog_id
     * @param Int $user_id
     * @param String $role
     * @return Bool true
     * @throws Exception hvis opprettelse feilet
     */
    public static function leggTilBruker(Int $blog_id, Int $user_id, String $role)
    {
        static::controlBlogId($blog_id);
        $result = add_user_to_blog($blog_id, $user_id, $role);
        if ($result) {
            return true;
        }
        throw new Exception(
            'Kunne ikke legge til bruker (' . $user_id . '). Wordpress sa: ' .
                implode(', ', $result->errors),
            172001
        );
    }

    /**
     * Fjern flere brukere fra blogg
     *
     * @param Int $blog_id
     * @param Array $users [id, role]
     * @return Array $rapport
     */
    public function fjernBrukere(Int $blog_id, array $users)
    {
        static::controlBlogId($blog_id);
        $rapport = [];
        foreach ($users as $user) {
            try {
                static::controlUserData($user);
                static::fjernBruker($blog_id, $user->id);
                $rapport[] = [
                    'success' => true,
                    'user' => $user
                ];
            } catch (Exception $e) {
                $rapport[] = [
                    'success' => false,
                    'user' => $user,
                    'message' => $e->getMessage(),
                    'code' => $e->getCode()
                ];
            }
        }
        return $rapport;
    }

    /**
     * Fjern bruker fra blogg
     *
     * @param Int $blog_id
     * @param Int $user_id
     * @return Bool true
     * @throws Exception hvis fjerning feilet
     */
    public static function fjernBruker(Int $blog_id, Int $user_id)
    {
        static::controlBlogId($blog_id);
        $result = remove_user_from_blog($user_id, $blog_id);
        if ($result) {
            return true;
        }
        throw new Exception(
            'Kunne ikke fjerne bruker (' . $user_id . ') fra blogg. Wordpress sa: ' .
                implode(', ', $result->errors),
            172002
        );
    }

    /**
     * Fjern alle brukere fra en blogg
     *
     * @param Int $blog_id
     * @return self::fjernBrukere()
     */
    public static function fjernAlleBrukere(Int $blog_id)
    {
        if ($blog_id == 1) {
            throw new Exception(
                'Kan ikke fjerne alle brukere fra hoved-siden',
                172006
            );
        }
        static::controlBlogId($blog_id);
        $users = get_users(array('blog_id' => $blog_id));

        $brukere = [];

        foreach ($users as $user) {
            if ($user->ID == 1) {
                continue;
            }
            $brukere[] = [
                'id' => $user->ID,
                'role' => ''
            ];
        }

        return static::fjernBrukere($blog_id, $brukere);
    }

    /**
     * Sjekk at bruker-data inneholder riktige felt
     *
     * @param Array $user
     * @return void
     * @throws Exception hvis noe er feil
     */
    public static function controlUserData($user)
    {
        if (!is_array($user)) {
            throw new Exception(
                'Bruker-data må være array med id og rolle definert',
                172003
            );
        }
        if (!isset($user['id'])) {
            throw new Exception(
                'Bruker-data må ha definert id-felt',
                172004
            );
        }
        if (!isset($user['role'])) {
            throw new Exception(
                'Bruker-data må ha definert role-felt',
                172005
            );
        }
    }

    /**
     * Sjekk at gitt blog-id er en gyldig id å jobbe med
     *
     * @param Any $blog_id
     * @return void
     */
    public static function controlBlogId($blog_id)
    {
        if (empty($blog_id)) {
            throw new Exception('Gitt BlogId er tom');
        }
        if (!is_numeric($blog_id)) {
            throw new Exception('Gitt BlogId er ikke numerisk');
        }
        if ($blog_id == 1) {
            throw new Exception('Gitt BlogId er 1 (hovedbloggen!)');
        }
        return $blog_id;
    }

    /**
     * Overfør data fra arrangementet til bloggen
     *
     * @param Int $blog_id
     * @param Arrangement $arrangement
     * @return void
     */
    public static function setArrangementData(Int $blog_id, Arrangement $arrangement)
    {
        static::controlBlogId($blog_id);

        $meta = [
            'blogname'          => $arrangement->getNavn(),
            'blogdescription'   => 'UKM i ' . $arrangement->getNavn(),
            'fylke'             => $arrangement->getFylke()->getId(),
            'site_type'         => $arrangement->getType(),
            'pl_id'             => $arrangement->getId(),
            'ukm_pl_id'         => $arrangement->getId(),
            'season'            => $arrangement->getSesong(),
        ];
        if ($arrangement->getType() == 'kommune') {
            $meta['kommuner'] = implode(',', $arrangement->getKommuner()->getIdArray());
        }
        static::applyMeta($blog_id, $meta);
    }

    /**
     * Sett standard-innstillinger på bloggen
     * Tema, forside osv
     *
     * @param Int $blog_id
     * @return void
     */
    public static function setStandardInnstillinger(Int $blog_id)
    {
        static::controlBlogId($blog_id);

        # ADDS META OPTIONS TO NEW SITE
        $meta = array(
            'show_on_front'        => 'posts',
            'page_on_front'        => '2',
            'template'            => 'UKMresponsive',
            'stylesheet'            => 'UKMresponsive',
            'current_theme'        => 'UKM Responsive',
            'status_monstring'    => false
        );
        static::applyMeta($blog_id, $meta);
    }

    public static function setStandardInnhold(Int $blog_id, String $arrangement_type)
    {
        static::controlBlogId($blog_id);
        switch_to_blog($blog_id);

        // Kategorier
        $cat_defaults = array(
            'cat_name' => 'Nyheter',
            'category_description' => 'nyheter',
            'category_nicename' => 'Nyheter',
            'category_parent' => 0,
            'taxonomy' => 'category'
        );
        wp_insert_category($cat_defaults);
        // Sider
        $sider = [
            ['id' => 'forside', 'name' => 'Forside', 'viseng' => null],
            ['id' => 'nyheter', 'name' => 'Nyheter', 'viseng' => null],
            ['id' => 'bilder', 'name' => 'Bilder', 'viseng' => 'bilder'],
            ['id' => 'pameldte', 'name' => 'Påmeldte', 'viseng' => 'pameldte'],
            ['id' => 'program', 'name' => 'Program', 'viseng' => 'program'],
            ['id' => 'kontaktpersoner', 'name' => 'Kontaktpersoner', 'viseng' => 'kontaktpersoner'],
        ];
        if ($arrangement_type !== 'kommune') {
            $sider[] = ['id' => 'lokalmonstringer', 'name' => 'Lokalmønstringer', 'viseng' => 'lokalmonstringer'];
        }

        foreach ($sider as $side) {
            $page = array(
                'post_type' => 'page',
                'post_title' => $side['name'],
                'post_status' => 'publish',
                'post_author' => 1,
                'post_slug' => $side['id'],
            );
            // Finnes siden fra før?
            $eksisterer = get_page_by_path($side['id']);
            if ($eksisterer == null) {
                $page_id = wp_insert_post($page);
            } else {
                $page_id = $eksisterer->ID;
            }
            if (isset($side['viseng']) && !empty($side['viseng'])) {
                // Først delete, så add, fordi update_post_meta ikke gjør nok
                // (hvis current_value er et array, vil update_post_meta 
                // ikke gjøre noe/oppdatere alle verdiene (uvisst)
                //
                // VISENG håndterer arrays i visningen, men det er likevel greit å 
                // ha riktig data.
                delete_post_meta($page_id, 'UKMviseng');
                add_post_meta($page_id, 'UKMviseng', $side['viseng']);
            }
        }

        // Slett "hei verden"
        $hello_world = get_page_by_path('hei-verden', OBJECT, 'post');
        if (is_object($hello_world)) {
            wp_delete_post($hello_world->ID);
        }

        $page_on_front = get_page_by_path('forside');
        $page_for_posts = get_page_by_path('nyheter');

        // Sett standard visningssider
        update_blog_option($blog_id, 'show_on_front', 'posts'); // 2019-02-07: endret til posts for at paginering skal funke
        update_blog_option($blog_id, 'page_on_front', $page_on_front->ID);
        update_blog_option($blog_id, 'page_for_posts', $page_for_posts->ID);

        restore_current_blog();
    }

    /**
     * Sett meta-data på bloggen
     *
     * @param Int $blog_id
     * @param Array $meta
     * @return void
     */
    public static function applyMeta(Int $blog_id, array $meta)
    {
        static::controlBlogId($blog_id);
        foreach ($meta as $key => $value) {
            add_blog_option($blog_id, $key, $value);
            update_blog_option($blog_id, $key, $value, true);
        }
    }
}
