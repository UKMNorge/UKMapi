<?php

namespace UKMNorge\Wordpress;

use Exception;
use stdClass;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Geografi\Fylke;
use UKMNorge\Geografi\Kommune;

require_once('UKM/Autoloader.php');

class Blog
{

    private static $hovedBloggId = 1;

    /**
     * @TODO
     * PORT: write_wp_blog:: splitt, flippBlogger, moveBlog
     */

    /**
     * Er gitt path tilgjengelig for blogg-opprettelse?
     *
     * @param String $path
     * @return boolean
     */
    public static function isAvailablePath(String $path)
    {
        $path = str_replace(
            [
                'https://'.UKM_HOSTNAME,
                'http://'. UKM_HOSTNAME,
                '//'. UKM_HOSTNAME
            ],
            '',
            $path
        );
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
     * @throws Exception 172007 hvis blogg ikke finnes
     * @return Int $id
     */
    public static function getIdByPath(String $path)
    {
        static::_requiresWordpressFunctions();
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
     * Hent bloggens path fra Id
     *
     * @param Int $id
     * @return String $path
     */
    public static function getPathById( Int $id ) {
        static::_requiresWordpressFunctions();
        $path = static::getDetails($id, 'path');
        if( !is_string( $path ) ) {
            throw new Exception(
                'Finner ikke blogg '. $id,
                172009
            );
        }
        return $path;
    }

    /**
     * Sikre at path er innenfor vår standard
     *
     * @ developer: hvis denne endres, må også funksjonen i UKMNorge\Geografi\Kommune endres!
     * 
     * @param String $path
     * @return String $path
     */
    public static function sanitizePath(String $path)
    {
        return preg_replace(
            "/[^a-z0-9-]/",
            '',
            str_replace(
                ['æ', 'ø', 'å', 'ü', 'é', 'è'],
                ['a', 'o', 'a', 'u', 'e', 'e'],
                mb_strtolower($path)
            )
        );
    }

    /**
     * Fetch blog details
     * 
     * @see Wordpress get_blog_details https://codex.wordpress.org/WPMU_Functions/get_blog_details
     *
     * @param Int $blog_id
     * @param String $variable
     * @return void
     */
    public static function getDetails( Int $blog_id, String $variable=null ) {
        static::_requiresWordpressFunctions();
        if( $variable == null ) {
            return get_blog_details( $blog_id );
        }
        return get_blog_details( $blog_id )->$variable;
    }

    /**
     * Update blog details
     *
     * @param Int $blog_id
     * @param Array $key_val_details
     * @return update_blog_details( $blog_id, $key_val_details );
     */
    public static function setDetails( Int $blog_id, Array $key_val_details ) {
        static::_requiresWordpressFunctions();
        return update_blog_details( $blog_id, $key_val_details);
    }

    /**
     * Get blog options
     *
     * @param Int $blog_id
     * @param String $option_name
     * @return get_blog_option( $blog_id, $option_name );
     */
    public static function getOption( Int $blog_id, String $option_name ) {
        static::_requiresWordpressFunctions();
        return get_blog_option( $blog_id, $option_name );
    }

    /**
     * Set / update set blog option
     *
     * @param Int $blog_id
     * @param String $option_name
     * @param Any $option_value
     * @return update_blog_option( $blog_id, $option_name, $option_value)
     */
    public static function setOption( Int $blog_id, String $option_name, $option_value ) {
        static::_requiresWordpressFunctions();
        return update_blog_option( $blog_id, $option_name, $option_value );
    }

    /**
     * Delete blog option
     *
     * @param Int $blog_id
     * @param String $option_name
     * @return delete_blog_option( $blog_id, $option_name)
     */
    public static function deleteOption( Int $blog_id, String $option_name ) {
        static::_requiresWordpressFunctions();
        return delete_blog_option( $blog_id, $option_name );
    }
    /**
     * Flytt en blogg til ny path
     *
     * @param Int $blog_id
     * @param String $path
     * @return void
     */
    public static function flytt( Int $blog_id, String $path ) {
        // Sjekk at vi har gyldig input-data
        static::controlBlogId( $blog_id );
        $path = static::controlPath( $path );
        // Sjekk at blogg som skal flyttes eksisterer (getPathById kaster exception hvis ikke)
        static::getPathById($blog_id);

        // Sjekk at ny path ikke er tatt
        try {
            static::getIdByPath( $path );
        } catch( Exception $e ) {
            // 172007 = ingen blog @ path, som er riktig
            if( $e->getCode() != 172007 ) {
                var_dump( $path );
                throw $e;
            }
        }

        $domain = 'https://'. rtrim(static::getDetails( $blog_id, 'domain' ),'/').'/';
        $url = rtrim($domain,'/').$path;
        
        $res = static::setDetails( 
            $blog_id,
            [ 'path' => $path]
        );
        if( !$res ) {
            throw new Exception(
                'Kunne ikke flytte blogg '. $blog_id .' til `'. $path .'`',
                172012
            );
        }
        static::setOption( $blog_id, 'siteurl', $url);
        static::setOption( $blog_id, 'home', $url);
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
        $blog_id = static::opprett( $path, $arrangement->getNavn());
        static::oppdaterFraArrangement($blog_id, $arrangement);

        // Disse skal ikke endres etter bloggen er opprettet, 
        // og settes derfor her, da f.eks. oppdaterForArrangement
        // kalles ved lagring av arrangement, og kan overskrive
        // site_type:kommune (som ikke skal skje)
        static::applyMeta(
            $blog_id, 
            [
                'site_type'         => 'arrangement',
                'pl_eier_type'      => $arrangement->getEierType(),
                'pl_eier_id'        => $arrangement->getEier()->getId(),
            ]
        );

        return $blog_id;
    }

    /**
     * Aktiver en blogg
     *
     * @param Int $blog_id
     * @return void
     */
    public static function aktiver( Int $blog_id ) {
        return Blog::setDetails(
            $blog_id,
            [
                'deleted' => false
            ]
        );
    }

    /**
     * Deaktiver en blogg
     *
     * @param Int $blog_id
     * @return void
     */
    public static function deaktiver( Int $blog_id ) {
        return Blog::setDetails(
            $blog_id,
            [
                'deleted' => true
            ]
        );
    }

    /**
     * Er bloggen deaktivert?
     *
     * @param Int $blog_id
     * @return Bool
     */
    public static function erDeaktivert( Int $blog_id ) {
        return Blog::getDetails( $blog_id, 'deleted' );
    }


    /**
     * Opprett en blogg for et fylke
     *
     * @param fylke $fylke
     * @return Int $blog_id
     */
    public static function opprettForFylke(Fylke $fylke)
    {
        $path = static::controlPath(
            static::sanitizePath( '/'. $fylke->getNavn() )
        );
        $blog_id = static::opprett( $path, $fylke->getNavn());        
        
        static::oppdaterFraFylke( $blog_id, $fylke );
        return $blog_id;
    }

    /**
     * Opprett en blogg for en kommune/bydel
     *
     * @param kommune $kommune
     * @return Int $blog_id
     */
    public static function opprettForKommune(Kommune $kommune)
    {
        $path = static::controlPath(
            static::sanitizePath( '/'. $kommune->getNavn() )
        );
        $blog_id = static::opprett( $path, $kommune->getNavn());
        
        static::oppdaterFraKommune( $blog_id, $kommune );
    
        return $blog_id;
    }


    /**
     * Oppdater en blogg med alle fylke-options
     *
     * @param Int $blog_id
     * @param Fylke $fylke
     * @return void
     */
    public static function oppdaterFraFylke( Int $blog_id, Fylke $fylke ) {
        static::applyMeta(
            $blog_id, 
            [
                'fylke'             => $fylke->getId(),
                'site_type'         => 'fylke',
                'blogdescription'   => 'UKM i '. $fylke->getNavn(),
                'blogname'          => $fylke->getNavn()
            ]
        );
    }


    /**
     * Oppdater en blogg med alle kommune-options
     *
     * @param Int $blog_id
     * @param Kommune $kommune
     * @return void
     */
    public static function oppdaterFraKommune( Int $blog_id, Kommune $kommune ) {
        static::applyMeta(
            $blog_id, 
            [
                'site_type'         => 'kommune',
                'kommune'           => $kommune->getId(),
                'fylke'             => $kommune->getFylke()->getId(),
                'kommuner'          => $kommune->getId(),
                'blogdescription'   => 'UKM i '. $kommune->getNavn(),
                'blogname'          => $kommune->getNavn()
            ]
        );

        // settes f.eks. hvis arrangementet er avlyst 
        static::deleteOption( $blog_id, 'status_monstring' );
        // sikre at alle standard-sider er der (bug pre 2020)
        static::setStandardSider($blog_id);
    }

    /**
     * Opprett en blogg
     *
     * @param String $path
     * @param String $navn
     * @return Int $blog_id
     */
    public static function opprett( String $path, String $navn ) {
        static::_requiresWordpressFunctions();
        if (static::eksisterer($path)) {
            throw new Exception(
                'Kunne ikke opprette blogg da siden allerede eksisterer',
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
                'title' => $navn
            ]
        );
        if( is_numeric( $blog_id ) ) {
            $blog_id = (Int) $blog_id;
        }
        static::controlBlogId($blog_id);
        static::setStandardInnstillinger( $blog_id );
        static::setStandardInnhold($blog_id);

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
        static::setArrangementData($blog_id, $arrangement);
        static::setStandardInnholdArrangement($blog_id, $arrangement->getType());
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
        // Sleng på leading slash
        // WP slenger selv på trailing slash
        if (strpos($path, '/') === false) {
            $path = '/' . $path;
        }
        return $path;
    }

    /**
     * Sjekk om en gitt bruker tilhører en gitt blogg - uavhengig av rolle.
     * Fungerer kun i Wordpress-kontekst.
     * 
     * @param Int $blog_id
     * @param UKMNorge\Wordpress\User - Bruker-objekt.
     * 
     * @return bool true hvis ja, false hvis nei.
     */
    public static function harBloggBruker(Int $blog_id, User $user) {
        # Prøv å opprett WP_User med oppgitt data. Om den finnes og har en rolle er alt OK
        $wp_users = get_users(['blog_id' => $blog_id, 'search' => $user->getId()]);
        if( isset( $wp_users[0] ) ) {
            $wp_user = $wp_users[0];
        }

        if( empty($wp_user->roles) ) {
            return false;
        }
        return true;
    }

    /**
     * Sjekk om en gitt bruker har rettigheter på hovedbloggen.
     * Fungerer kun i Wordpress-kontekst.
     * UKMusers-brukere skal kun ha rettigheten "subscriber".
     * 
     * @param UKMNorge\Wordpress\User - Bruker-objekt.
     * @param String $role - optional. Filtrer på denne rollen.
     * 
     * @return bool true hvis ja, false hvis nei.
     */
    public static function harHovedbloggBruker( User $user, String $role = null ) {
        # Prøv å opprett WP_User med oppgitt data. Om den finnes og har en rolle er alt OK
        $wp_users = get_users(['blog_id' => static::$hovedBloggId, 'search' => $user->getId()]);
        if( isset( $wp_users[0] ) ) {
            $wp_user = $wp_users[0];
        }

        if( empty($wp_user->roles) ) {
            return false;
        }

        if( null == $role ) {
            // Don't bother checking roles
            return true;
        }

        if( !in_array($role, $wp_user->roles) ) {
            return false;
        }
        return true;
    }

    /**
     * Legg til en ny bruker på hovedbloggen.
     * Default rettighet for alle UKMusers-brukere er 'subscriber', men vi støtter å legge til andre.
     * 
     * @param UKMNorge\Wordpress\User $user - Bruker-objekt.
     * @param String $role - optional. Default 'subscriber'.
     * 
     * @return bool $result. True hvis OK, false hvis feil.
     * @throws Exception ved feil.
     */
    public static function leggTilHovedbloggBruker( User $user, String $role = 'subscriber' ) {
        static::_requiresWordpressFunctions();
        static::controlBlogId(static::$hovedBloggId, true);
        $result = add_user_to_blog(static::$hovedBloggId, $user->getId(), $role);
        if ($result) {
            return true;
        } 
        return false;
    }

    /**
     * Legg til flere brukere til blogg
     *
     * @param Int $blog_id
     * @param Array $users [id, role]
     * @return Array $rapport
     */
    public static function leggTilBrukere(Int $blog_id, Array $users)
    {
        static::_requiresWordpressFunctions();
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
        static::_requiresWordpressFunctions();
        static::controlBlogId($blog_id, true);
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
        static::_requiresWordpressFunctions();
        static::controlBlogId($blog_id);
        $rapport = [];
        foreach ($users as $user) {
            try {
                static::controlUserData($user);
                static::fjernBruker($blog_id, $user['id']);
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
        static::_requiresWordpressFunctions();
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
        static::_requiresWordpressFunctions();
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
     * @param Bool $allowBlogIdToBeOne
     * @return void
     */
    public static function controlBlogId( Int $blog_id, Bool $allowBlogIdToBeOne=false)
    {
        if (empty($blog_id)) {
            throw new Exception('Gitt BlogId er tom');
        }
        if (!is_numeric($blog_id)) {
            throw new Exception('Gitt BlogId er ikke numerisk');
        }
        if ($blog_id == 1 && !$allowBlogIdToBeOne ) {
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
            'pl_eier_type'      => $arrangement->getEierType(),
            'pl_eier_id'        => $arrangement->getEier()->getId(),
            'pl_id'             => $arrangement->getId(),
            'season'            => $arrangement->getSesong(),
            'blogname'          => $arrangement->getNavn()
        ];
        if ($arrangement->getType() == 'kommune') {
            $meta['kommuner']           = implode(',', $arrangement->getKommuner()->getIdArray());
        }
        
        if( in_array($arrangement->getEierType(), ['kommune','fylke'])) {
            $meta['fylke'] = $arrangement->getFylke()->getId();
        }

        static::applyMeta($blog_id, $meta);
    }

    /**
     * Slett all arrangement-data fra en gitt blogg
     *
     * @param Int $blog_id
     * @return void
     */
    public static function fjernArrangementData( Int $blog_id ) {
        // META-DATA
        $metas = [
            'pl_id',
            'season',
            'kommuner',
            'ukm_pl_id',
            'status_monstring' // settes f.eks. hvis arrangementet er avlyst
        ];

        foreach( $metas as $meta ) {
            #echo 'delete '. $meta .' from '. $blog_id .'<br />';
            delete_blog_option( $blog_id, $meta );
        }

        // STANDARD-SIDER
        $pages = [
            'bilder',
            'pameldte',
            'program',
            // 'kontaktpersoner', // kontaktpersoner skal ikke slettes fordi det brukes på kommune/fylke sider
            'lokalmonstringer',
            'info' // TODO: sjekk om dette er slug for standard infoside
        ];
        static::fjernSider( $blog_id, $pages );
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

        $currentSiteUrl = get_blog_option($blog_id, 'siteurl', '');
        $newSiteUrl = str_replace('http://', 'https://', $currentSiteUrl);

        # ADDS META OPTIONS TO NEW SITE
        $theme = static::getCurrentTheme();
        $meta = array(
            'show_on_front'       => 'posts',
            'page_on_front'       => '2',
            'template'            => $theme->template,
            'stylesheet'          => $theme->stylesheet,
            'current_theme'       => $theme->name,
            'status_monstring'    => false,
            'siteurl'             => $newSiteUrl,
            'home'                => $newSiteUrl
        );
        static::applyMeta($blog_id, $meta);
    }

    /**
     * Hent aktivt tema
     *
     * @return stdClass
     */
    public static function getCurrentTheme() {
        $data = new stdClass();
        $data->name = 'UKM-design: Wordpress';
        $data->template = 'UKMDesignWordpress';
        $data->stylesheet = 'UKMDesignWordpress';
        return $data;
    }

    /**
     * Aktiver standard-tema
     *
     * @param Int $blog_id
     * @return void
     */
    public static function enableCurrentTheme(Int $blog_id) {
        static::_requiresWordpressFunctions();
        static::controlBlogId($blog_id);
        static::switchTo($blog_id);
        switch_theme( static::getCurrentTheme()->stylesheet );
        static::restore();
    }

    /**
     * Legg til standard-innhold på bloggen
     *
     * @param Int $blog_id
     * @return void
     */
    public static function setStandardInnhold(Int $blog_id)
    {
        static::controlBlogId($blog_id);
        // Det finnes jo noen sider som ikke skal ha standard-innholdet,
        // og som ikke skal følge standardinstillinger for page_on_front osv
        if( !in_array( static::getOption($blog_id, 'site_type'), ['kommune','fylke','land','arrangement']) ) {
            return true;
        }
        
        // Kategorier
        static::leggTilKategorier(
            $blog_id,
            [
                [
                    'cat_name' => 'Nyheter',
                    'category_description' => 'nyheter',
                    'category_nicename' => 'Nyheter',
                    'category_parent' => 0,
                    'taxonomy' => 'category'
                ]
            ]
        );
        
        // Sider
        static::setStandardSider($blog_id);

        // Fjern standard-sider
        static::fjernSider(
            $blog_id,
            [
                'hei-verden',
                'testside'
            ]
        );

        // Sett standard-front
        static::switchTo($blog_id);
        $page_on_front = get_page_by_path('forside');
        $page_for_posts = get_page_by_path('nyheter');
        static::restore();

        // Sett standard visningssider
        update_blog_option($blog_id, 'show_on_front', 'posts'); // 2019-02-07: endret til posts for at paginering skal funke
        update_blog_option($blog_id, 'page_on_front', $page_on_front->ID);
        update_blog_option($blog_id, 'page_for_posts', $page_for_posts->ID);
    }

    /**
     * Oppretter standardsidene som alle blogger har
     * 
     * (gjelder både arrangement-, kommune- og fylkessider)
     *
     * @param Int $blog_id
     * @return void
     */
    public static function setStandardSider(Int $blog_id) {
        static::leggTilSider(
            $blog_id,
            [
                ['id' => 'forside', 'name' => 'Forside', 'viseng' => null],
                ['id' => 'nyheter', 'name' => 'Nyheter', 'viseng' => null],
                ['id' => 'kontaktpersoner', 'name' => 'Kontaktpersoner', 'viseng' => 'kontaktpersoner']
            ]
        );
    }

    /**
     * Sett standard-innhold for en arrangement-side
     *
     * @param Int $blog_id
     * @param String $arrangement_type
     * @return void
     */
    public static function setStandardInnholdArrangement( Int $blog_id, String $arrangement_type) {
        $sider = [
            ['id' => 'bilder', 'name' => 'Bilder', 'viseng' => 'bilder'],
            ['id' => 'pameldte', 'name' => 'Påmeldte', 'viseng' => 'pameldte'],
            ['id' => 'program', 'name' => 'Program', 'viseng' => 'program'],
            ['id' => 'kontaktpersoner', 'name' => 'Kontaktpersoner', 'viseng' => 'kontaktpersoner'],
        ];
        if ($arrangement_type !== 'kommune') {
            $sider[] = ['id' => 'lokalmonstringer', 'name' => 'Lokalmønstringer', 'viseng' => 'lokalmonstringer'];
        }
        static::leggTilSider( $blog_id, $sider );
    }

    /**
     * Legg til et array av sider til gitt blogg
     * Eksempel-data side: ['id' => 'bilder', 'name' => 'Bilder', 'viseng' => 'bilder']
     * Eksempel-data side: ['id' => 'forside', 'name' => 'Forside', 'viseng' => null]
     *
     * @param Int $blog_id
     * @param Array $sider
     * @return void
     */
    public static function leggTilSider( Int $blog_id, Array $sider ) {
        static::switchTo($blog_id);

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
                // Tving ønsket slug
                $page['post_title'] = $page['post_slug'];
                $page_id = (Int) wp_insert_post($page);
                // Oppdater sidens navn (fordi vi tvinger frem slug tidligere)
                wp_update_post(
                    [
                        'ID' => $page_id,
                        'post_title' => $side['name']
                    ]
                );
            } else {
                $page_id = (Int) $eksisterer->ID;
            }
            if (isset($side['viseng']) && !empty($side['viseng'])) {
                static::setSideMeta( $blog_id, $page_id, ['UKMviseng' => $side['viseng']], true);
            }
        }
        static::restore();
    }

    /**
     * Opprett en side
     *
     * @param Int $blog_id
     * @param String $slug
     * @param String $navn
     * @param String $viseng
     * @return Int $page_id
     */
    public static function opprettSide( Int $blog_id, String $slug, String $navn, String $viseng = null ) {
        $side = [
            'id' => $slug,
            'name' => $navn
        ];
        if( !empty( $viseng ) ) {
            $side['viseng'] = $viseng;
        }
        static::leggTilSider( $blog_id, [$side] );
        return (Int) get_page_by_path($slug)->ID;
    }

    public static function oppdaterSideInnhold( Int $blog_id, String $slug, String $content ) {
        $side = static::hentSideByPath($blog_id, $slug);
        wp_update_post(
            [
                'ID' => $side->ID,
                'post_content' => $content
            ]
        );
    }

    /**
     * Set meta-data for en page (/post?)
     * 
     * Bytter default frem og tilbake til gitt $blog_id, 
     * hvis ikke $switched == true
     *
     * @param Int $blog_id
     * @param Int $page_id
     * @param Array $meta
     * @param Bool $switched
     * @return void
     */
    public static function setSideMeta( Int $blog_id, Int $page_id, Array $meta, Bool $switched = false ) {
        if( !$switched ) {
            static::switchTo($blog_id);
        }
        foreach( $meta as $key => $value ) {
            // Først delete, så add, fordi update_post_meta ikke gjør nok
            // (hvis current_value er et array, vil update_post_meta 
            // ikke gjøre noe/oppdatere alle verdiene (uvisst)
            //
            // VISENG håndterer arrays i visningen, men det er likevel greit å 
            // ha riktig data.
            delete_post_meta($page_id, $key);
            add_post_meta($page_id, $key, $value);
        }
        if( !$switched ) {
            static::restore();
        }
    }

    /**
     * Hent en side far path
     *
     * @param String $path
     * @return WP_Post
     * @throws Exception hvis ikke finnes
     */
    public static function hentSideByPath( Int $blog_id, String $path ) {
        static::switchTo($blog_id);
        $page = get_page_by_path( $path );
        static::restore();
        
        if( is_null( $page ) ) {
            throw new Exception(
                'Fant ikke side '. $path,
                172011
            );
        }
        
        return $page;
    }

    /**
     * Har bloggen denne siden?
     *
     * @param String $path
     * @return Bool
     */
    public static function harSide( Int $blog_id, String $path ) {
        try {
            static::hentSideByPath( $blog_id, $path );
            return true;
        } catch( Exception $e ) {}
        return false;
    }

    /**
     * Fjern et array av page-slugs fra gitt blogg
     *
     * @param Int $blog_id
     * @param Array $sider
     * @return void
     */
    public static function fjernSider( Int $blog_id, Array $sider ) {
        static::switchTo($blog_id);
        foreach( $sider as $side_path ) {
            static::fjernSide($blog_id, $side_path);
        }
        static::restore();
    }

    /**
     * Fjern en gitt side fra page-slug
     *
     * @param Int $blog_id
     * @param String $path
     * @param Bool $switched
     * @return void
     */
    public static function fjernSide( Int $blog_id, String $path, Bool $switched = false ) {
        if( !$switched ) {
            static::switchTo($blog_id);
        }

        $page = get_page_by_path($path, OBJECT, 'page');
        if (is_object($page)) {
            wp_delete_post($page->ID);
        }

        if( !$switched ) {
            static::restore();
        }
    }

    /**
     * Har denne bloggen publiserte innlegg?
     *
     * @param Int $blog_id
     * @return Bool
     */
    public static function harPosts( Int $blog_id ) {
        return static::getAntallPosts( $blog_id ) > 0;
    }

    /**
     * Antall publiserte innlegg bloggen har
     *
     * @param Int $blog_id
     * @return Int antall publiserte innlegg
     */
    public static function getAntallPosts( Int $blog_id ) {
        static::switchTo($blog_id);
        $count = wp_count_posts();
        static::restore();
        if( !isset( $count->publish ) ) {
            return 0;
        }
        return (Int) $count->publish;
    }

    /**
     * Legg til et array med kategorier til gitt blogg
     * Eksempel-data kategori:  [
     *          'cat_name' => 'Nyheter',
     *          'category_description' => 'nyheter',
     *          'category_nicename' => 'Nyheter',
     *          'category_parent' => 0,
     *          'taxonomy' => 'category'
     *     ]
     * 
     * 
     * @param Int $blog_id
     * @param Array $kategorier
     * @return void
     */
    public static function leggTilKategorier( Int $blog_id, Array $kategorier ) {
        static::switchTo($blog_id);

        foreach( $kategorier as $kategori ) {
            wp_insert_category($kategori);
        }
        
        static::restore();
    }

    /**
     * Oppdater blogger når et arrangement avlyses
     * 
     * Avlyser ikke selve arrangementet, men rydder opp i blogg-
     * delen av det.
     *
     * @param Int $blog_id
     * @return void
     */
    public static function avlys( Int $blog_id ) {
        $arrangement_id = static::getOption( $blog_id, 'pl_id' );
        if( !$arrangement_id ) {
            throw new Exception(
                'Blog ('. $blog_id .') har ikke angitt arrangement-ID (pl_id)',
                572001
            );
        }
        $arrangement = new Arrangement( $arrangement_id );
        $sesong = $arrangement->getSesong();
        $omrade = $arrangement->getEierOmrade();
        
        $eier = $arrangement->getEier();
        $eier_path = trim($eier->getPath(),'/');
        $slett_path = trim($arrangement->getPath(),'/');
        
        // Fjern arrangement-data
        static::fjernArrangementData( $blog_id );

        // Er gitt blogg kommunesiden?
        if( $eier_path == $slett_path && $arrangement->getEierType() == 'kommune') {
            // Sikre at kommune-info er oppdatert
            static::oppdaterFraKommune( $blog_id, $arrangement->getEierKommune() );
        }
        // Dette er en ren arrangementsside
        // (fylkessider er aldri tilknyttet ett arrangement)
        else {
            static::deaktiver( $blog_id );
            if( $arrangement->getEierType() == 'kommune' ) {
                static::setOption( 
                    $blog_id, 
                    'kommuner', 
                    join(',',$arrangement->getKommuner()->getIdArray())
                );
            }
            static::setOption($blog_id, 'status_monstring', 'avlyst');
            static::setOption($blog_id, 'fylke', $arrangement->getFylke()->getId());
            static::fjernAlleBrukere($blog_id);
        }

        // Hvis det er en kommune, som nå har bare ett arrangement, så skal dette på fronten
        if( $arrangement->getEierType() == 'kommune' ) {
            $count = 0;
            $arrangement_som_skal_overta = false;
            foreach( $omrade->getArrangementer()->getAll() as $annet_arrangement ) {
                if( $annet_arrangement->getId() != $arrangement->getId() ) {
                    $count++;
                    if( !$annet_arrangement->erFellesmonstring() ) {
                        $arrangement_som_skal_overta = $annet_arrangement;
                    }
                }
            }
            if( $count == 1 && $arrangement_som_skal_overta) {
                // Deaktiver den gamle bloggen for arrangementet som overtar
                static::deaktiver( 
                    static::getIdByPath(
                        $arrangement_som_skal_overta->getPath()
                    )
                );

                static::oppdaterFraArrangement(
                    $blog_id, // blog_id == kommune-siden
                    $arrangement_som_skal_overta
                );
            }
        }
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
        static::_requiresWordpressFunctions();
        static::switchTo($blog_id);

        foreach ($meta as $key => $value) {
            add_blog_option($blog_id, $key, $value);
            update_blog_option($blog_id, $key, $value, true);
        }

        static::restore();
    }

    public static function switchTo( Int $blog_id ) {
        static::_requiresWordpressFunctions();
        static::controlBlogId($blog_id);
        switch_to_blog($blog_id);
    }

    public static function restore() {
        static::_requiresWordpressFunctions();
        restore_current_blog();
    }

    /**
     * Sjekk om vi er i wordpressEnvironment
     *
     * @return void
     * @throws Exception
     */
    private static function _requiresWordpressFunctions() {
        if( !function_exists('get_blog_option') ) {
            throw new Exception(
                'Blogg-operasjoner kan kun gjøres fra wordpress-environment',
                172010
            );
        }
    }

}
