<?php

namespace UKMNorge\Filmer\UKMTV;

use UKMNorge\Collection;
use Exception;
use UKMNorge\Database\SQL\Query;

class Filmer extends Collection
{

    var $is_empty = false;

    public $query;
    public $cfQuery;
    /**
     * Henter inn filmer basert på gitt spørring (via constructor)
     *
     * @return bool true
     * @throws Exception
     */
    public function _load()
    {
        if( $this->is_empty) {
            return true;
        }
        
        $res = null;
        
        // If there is no query. If there is no $query it means that just CloudFlare query ($cfQuery) may be used
        if($this->query != null) {
            $res = $this->query->run();
            
            if (!$res) {
                throw new Exception(
                    'Kunne ikke laste inn filmer, grunnet databasefeil',
                    115001
                );
            }

            while ($filmData = Query::fetch($res)) {
                // Hvis det er cloudflare, legg til CloudflareFilm
                if(array_key_exists('cloudflare', $filmData) && $filmData['cloudflare'] == 1) {
                    $film = new CloudflareFilm($filmData, $filmData['tv_id']);
                }
                else{
                    $film = new Film($filmData);
                }
                if ($film->erSlettet()) {
                    continue;
                }
                $this->add($film);
            }
        }

        // Legg til filmer fra CloudFlare Stream. Februar-mars 2023 migrerte vi nye filmene våre til CloudFlare Stream
        if($this->cfQuery) {
            $res2 = $this->cfQuery->run();
            if (!$res2) {
                throw new Exception(
                    'Kunne ikke laste inn CF filmer, grunnet databasefeil',
                    115008
                );
            }
            while ($cfFilmData = Query::fetch($res2)) {
                $film = new CloudflareFilm($cfFilmData);

                if ($film->erSlettet()) {
                    continue;
                }
                $this->add($film);
            }
        }

        return true;
    }

    /**
     * Opprett en ny samling filmer
     *
     * @param Query Spørring for å hente ut filmer
     */
    public function __construct(Query|null $query, Query $cfQuery=null)
    {
        $this->query = $query;
        $this->cfQuery = $cfQuery;
    }

    /**
     * Hent film gjennom cloudflare_id
     *
     * @param string $cfId
     * @return CloudflareFilm
     */
    public static function getByCFId(string $cfId) {
        // Cloudflare filmer
        $query = new Query(
            CloudflareFilm::getLoadQuery() . "
            WHERE `cloudflare_id` = '#cfId'
            AND `deleted` = 'false'",
            [
                'cfId' => $cfId
            ]
        );

        $dataCF = $query->getArray();
        
        if(!$dataCF) {
            throw new Exception(
                'Beklager! Klarte ikke å finne film ' . $cfId,
                115007
            );
        }

        $queryTags = new Query(
            "SELECT GROUP_CONCAT( CONCAT(`ukm_tv_tags`.`type`,':',`ukm_tv_tags`.`foreign_id` ) SEPARATOR '|') as result
            FROM `ukm_tv_tags`
            WHERE `tv_id` = '#filmId'
            AND `cloudflare` = '1'",
            [
                'filmId' => $dataCF['id']
            ]
        );

        $tags = $queryTags->run('array');
        $dataCF['tags'] = $tags['result'];

        return new CloudflareFilm($dataCF);
    }

    /**
     * Hent gitt film fra ID
     *
     * @param Int $tv_id
     * @return FilmInterface
     */
    public static function getLatest(Int $limit)
    {
        
        // Cloudflare filmer
        $queryCF = new Query(
            CloudflareFilm::getLoadQuery() . "
            ORDER BY id DESC LIMIT #limit",
            [
                'limit' => $limit
            ]
        );

        
        return new Filmer(null, $queryCF);
    }


    /**
     * Hent gitt film fra ID
     *
     * @param Int $tv_id
     * @return FilmInterface
     */
    public static function getById(Int $tv_id)
    {
        $query = new Query(
            Film::getLoadQuery() . "
            WHERE `tv_id` = '#tvid'
            AND `tv_deleted` = 'false'",
            [
                'tvid' => $tv_id
            ]
        );
        $data = $query->getArray();
        
        // Cloudflare filmer
        $queryCF = new Query(
            CloudflareFilm::getLoadQuery() . "
            WHERE `id` = '#tvid'
            AND `deleted` = 'false'",
            [
                'tvid' => $tv_id
            ]
        );

        $dataCF = $queryCF->getArray();
        
        if (!$data && !$dataCF) {
            throw new Exception(
                'Beklager! Klarte ikke å finne film ' . intval($tv_id),
                115007
            );
        }

        return $data ? new Film($data) : new CloudflareFilm($dataCF);
    }

    /**
     * Opprett en filmerCollection for gitt innslagId
     * Hvis argumentet arrangementId sendes så kan filmene hentes på et innslag i arrangement
     * Uten argumentet arrangementID blir alle filmer fra innslag forkjellige arrangementer
     *
     * @param Int $innslagId
     * @return Filmer
     */
    public static function getByInnslag(Int $innslagId, Int $arrangementId=null)
    {
        $query = new Query(
            Film::getLoadQuery() . "
            WHERE `b_id` = '#innslagId'
            AND `tv_deleted` = 'false'", // deleted ikke nødvendig, men gjør lasting marginalt raskere
            [
                'innslagId' => $innslagId
            ]
        );

        if($arrangementId == null) {
            $queryCF = new Query(
                CloudflareFilm::getLoadQuery() . "
                WHERE `innslag` = '#innslagId'
                AND `deleted` = 'false'", // deleted ikke nødvendig, men gjør lasting marginalt raskere
                [
                    'innslagId' => $innslagId
                ]
            );
        }
        else {
            $queryCF = new Query(
                CloudflareFilm::getLoadQuery() . "
                WHERE `innslag` = '#innslagId' AND `arrangement` = '#arrangementId'
                AND `deleted` = 'false'", // deleted ikke nødvendig, men gjør lasting marginalt raskere
                [
                    'innslagId' => $innslagId,
                    'arrangementId' => $arrangementId
                ]
            );
        }


        return new Filmer($query, $queryCF);
    }

    /**
     * Hent alle filmer fra ett arrangement
     *
     * @param Int $arrangementId
     * @return Filmer
     */
    public static function getByArrangement(Int $arrangementId)
    {
        return static::getByTag('arrangement', $arrangementId);
    }

    /**
     * Hent alle filmer for en gitt tag
     *
     * @param String $tag
     * @param Int $id
     * @return Filmer
     */
    public static function getByTag(String $tag, Int $id)
    {
        $query = new Query(
            Film::getLoadQuery() . "
            JOIN `ukm_tv_tags` 
            ON (
                `ukm_tv_tags`.`tv_id` = `ukm_tv_files`.`tv_id` 
                AND `ukm_tv_tags`.`type` = '#tagtype' 
                AND `ukm_tv_tags`.`foreign_id` = '#foreignid'
            )
            WHERE `tv_deleted` = 'false'
            GROUP BY `ukm_tv_files`.`tv_id`
            ORDER BY `tv_title` ASC",
            [
                'tagtype' => $tag,
                'foreignid' => $id
            ]
        );

        // Cloudflare filmer
        $queryCF = new Query(
            CloudflareFilm::getLoadQuery() . "
            JOIN `ukm_tv_tags` 
            ON (
                `cloudflare_videos`.`id` = `ukm_tv_tags`.`tv_id` 
                AND `ukm_tv_tags`.`type` = '#tagtype' 
                AND `ukm_tv_tags`.`foreign_id` = '#foreignid'
            )
            WHERE `deleted` = 'false'",
            [
                'tagtype' => $tag,
                'foreignid' => $id
            ]
        );

        return new Filmer($query, $queryCF);
    }

    /**
     * Har gitt arrangement (ferdig-konverterte) filmer i UKM-TV?
     *
     * @param Int $arrangementId
     * @return bool
     */
    public static function harArrangementFilmer(Int $arrangementId)
    {
        return static::harTagFilmer('arrangement', $arrangementId);
    }

    /**
     * Finnes det filmer for denne tag'en?
     *
     * @param String $tag
     * @param Int $tagId
     * @return void
     */
    public static function harTagFilmer(String $tag, Int $tagId)
    {
        $query = new Query(
            "SELECT `id`
            FROM `ukm_tv_tags` 
            WHERE `type` = '#tagnavn'
            AND `foreign_id` = '#tagid'
            LIMIT 1",
            [
                'tagnavn' => $tag,
                'tagid' => $tagId
            ]
        );
        return !!$query->getField(); # (dobbel nekting er riktig)
    }

    /**
     * Finnes det filmer med disse tag'ene?
     *
     * @param Array<Tag>
     * @return Bool
     */
    public static function harTagsFilmer(array $tags)
    {
        $query = new Query(
            static::_getTagQuery(sizeof($tags)) . " LIMIT 1",
            static::_getTagQueryReplacement($tags)
        );
        
        // Cloudflare filmer
        $queryCF = new Query(
            static::_getTagQueryCF(sizeof($tags)) . " LIMIT 1",
            static::_getTagQueryReplacement($tags)
        );
        
        return !!$query->getField() || !!$queryCF->getField(); # (dobbel nekting er riktig)        
    }

    /**
     * Hent alle filmer som har alle disse tag'ene
     *
     * @param Array<Tag> tags
     * @return Filmer
     */
    public static function getByTags(array $tags)
    {
        return new Filmer(
            new Query(
                static::_getTagQuery(sizeof($tags)),
                static::_getTagQueryReplacement($tags)
            ),
            
            // Cloudflare filmer
            new Query(
                static::_getTagQueryCF(sizeof($tags)),
                static::_getTagQueryReplacement($tags)
            )
        );
    }

    /**
     * Hent alle filmer fra søkestreng
     *
     * @param String $search_string
     * @return Filmer
     */
    public static function getBySearchString(String $search_string)
    {
        // SEARCH FOR TITLE AND BAND NAME (TV TITLE)
        $search_for = str_replace(',', ' ', $search_string);
        if (substr_count($search_for, ' ') == 0) {
            $where = " `tv_title` LIKE '%#title%'";
        } else {
            $where = "MATCH (`tv_title`) AGAINST('+#title' IN BOOLEAN MODE)";
        }
        $titles = [];
        $videos = [];
        $qry = new Query(
            "SELECT `tv_id`,
                    MATCH (`tv_title`) AGAINST('#title') AS `score`
                    FROM `ukm_tv_files`
                    WHERE $where
                    AND `tv_deleted` = 'false'",
            [
                'title' => $search_string
            ]
        );
        $res = $qry->run();
        $i = 0;
        if ($res) {
            while ($r = Query::fetch($res)) {
                $videos[$r['tv_id']] = $r['score'];
                $titles[] = $r['tv_id'];
            }
        }

        // Search by name at Cloudflare
        $cfNameSearch = static::searchNameCF($search_string);
        $cfVideos = $cfNameSearch['videos'];
        $cfTitles = $cfNameSearch['titles'];

        // Legger til videos på riktig indeks
        if(is_array($cfVideos)) {
            foreach($cfVideos as $key=>$value ) {
                $videos[$key] = $value;
            }
        }

        // Legger til titles på riktig indeks
        if(is_array($cfTitles)) {
            foreach($cfTitles as $key=>$value ) {
                $titles[$key] = $value;
            }
        }
        
        // SEARCH FOR PERSONS NAME
        $qry = new Query(
            // Første del av union er Cloudflare filmer
            "SELECT tv_id, p_firstname, score 
                FROM 
                (SELECT tv_id, p_firstname, p_id, MATCH (smartukm_participant.p_firstname, smartukm_participant.p_lastname) AGAINST('#title') as `score`
                    FROM smartukm_participant 
                    JOIN ukm_tv_tags ON smartukm_participant.p_id=ukm_tv_tags.foreign_id 
                    WHERE type='person'
                ) as derivedTable WHERE score<>0

            UNION
            
            SELECT `p`.`tv_id`, `p_name`,
                    MATCH (`p`.`p_name`) AGAINST('#title') AS `score`
                    FROM `ukm_tv_persons` AS `p`
                    LEFT JOIN `ukm_tv_files` AS `tv` ON (`tv`.`tv_id` = `p`.`tv_id`)
                    WHERE MATCH (`p`.`p_name`) AGAINST('#title' IN BOOLEAN MODE)
                    AND `tv`.`tv_deleted` = 'false'
                    ",
            [
                'title' => '+' . $search_string
            ]
        );
        $res = $qry->run();
        $i = 0;
        if ($res) {
            while ($r = Query::fetch($res)) {
                if (is_array($titles) && in_array($r['tv_id'], $titles)) {
                    $videos[$r['tv_id']] = $videos[$r['tv_id']] + $r['score'];
                } else
                    $videos[$r['tv_id']] = $r['score'];
            }
        }

        @arsort($videos);


        $filmer = [];
        if (is_array($videos)) {
            foreach ($videos as $id => $score) {
                $filmer[] = $id;
            }
        }

        if( sizeof($filmer) == 0 ) {
            $object = new Filmer(new Query(""));
            $object->is_empty = true;
            return $object;
        }

        return Filmer::getByIdList($filmer);
    }


    /**
     * Søk etter navn på Cloudflare Stream filmer
     *
     * @param String $search_string
     * @return Array
     */
    private static function searchNameCF(String $search_string) {
        $search_for = str_replace(',', ' ', $search_string);
        if (substr_count($search_for, ' ') == 0) {
            $where = " `title` LIKE '%#title%'";
        } else {
            $where = "MATCH (`title`) AGAINST('+#title' IN BOOLEAN MODE)";
        }
        $titles = [];
        $videos = [];
        $qry = new Query(
            "SELECT `id` as `tv_id`,
                    MATCH (`title`) AGAINST('#title') AS `score`
                    FROM `cloudflare_videos`
                    WHERE $where
                    AND `deleted` = 'false'",
            [
                'title' => $search_string
            ]
        );
        $res = $qry->run();
        $i = 0;
        if ($res) {
            while ($r = Query::fetch($res)) {
                $videos[$r['tv_id']] = $r['score'];
                $titles[] = $r['tv_id'];
            }
        }
        return ['videos' => $videos, 'titles' => $titles];
    }

    /**
     * Lag Query-parameter 2 fra et array med tags
     *
     * @param Array<Tag> $tags
     * @return Array
     */
    private static function _getTagQueryReplacement(array $tags)
    {
        $replace = [];
        $count = 0;
        foreach ($tags as $tag) {
            $count++;
            $replace['tagName' . $count] = $tag->getId();
            $replace['tagOperand' . $count] = is_array($tag->getValue()) ? 'IN' : '=';
            $replace['tagValue' . $count] = is_array($tag->getValue()) ? '(' . join(',', $tag->getValue()) . ')' : $tag->getValue();
        }
        return $replace;
    }

    /**
     * Hent flere filmer fra ID
     *
     * @param Array<Int>
     * @return Filmer
     */
    public static function getByIdList(array $idList)
    {
        return new Filmer(
            new Query(
                Film::getLoadQuery() . "
                WHERE `tv_id` IN (#list)
                AND `tv_deleted` = 'false'",
                [
                    'list' => join(',', $idList)
                ]
            ),

            // Hent filmer fra Cloudflare   
            new Query(
                CloudflareFilm::getLoadQuery() . "
                WHERE `id` IN (#list)
                AND `deleted` = 'false'",
                [
                    'list' => join(',', $idList)
                ]
            )
        );
    }

    private static function _getTagQueryCF(Int $number_of_tags) {
        $query = "SELECT *
            FROM `cloudflare_videos`";

        for ($i = 1; $i <= $number_of_tags; $i++) {
            $query .= "
            JOIN `ukm_tv_tags` AS `tag" . $i . "`
                ON (`cloudflare_videos`.`id` = `tag" . $i . "`.`tv_id` AND `tag" . $i . "`.`type`='#tagName" . $i . "' AND `tag" . $i . "`.`foreign_id` #tagOperand" . $i . " #tagValue" . $i . ")";
        }

        $query .= "    
            WHERE `cloudflare_videos`.`deleted` = 'false'
            ORDER BY `title` ASC";

        return $query;
    }


    /**
     * Lag SQL-spørring for å hente ut alle filmer med de gitte tag'ene
     *
     * @return String sql-query
     */
    private static function _getTagQuery(Int $number_of_tags)
    {
        $query = "SELECT *
            FROM `ukm_tv_files`";

        for ($i = 1; $i <= $number_of_tags; $i++) {
            $query .= "
            JOIN `ukm_tv_tags` AS `tag" . $i . "`
                ON (`ukm_tv_files`.`tv_id` = `tag" . $i . "`.`tv_id` AND `tag" . $i . "`.`type`='#tagName" . $i . "' AND `tag" . $i . "`.`foreign_id` #tagOperand" . $i . " #tagValue" . $i . ")";
        }

        $query .= "    
            WHERE `ukm_tv_files`.`tv_deleted` = 'false'
            ORDER BY `tv_title` ASC";

        return $query;
    }

    /**
     * Hent filmens ID fra URL-adresse
     *
     * @param String $url
     * @return Int $id
     */
    public static function getIdFromUrl( String $url ) {
        $url = rtrim($url, '/');
        $urldata = explode ('/', $url);
        $id = array_pop($urldata);
        
        if( is_null($id) || empty($id) || !is_numeric($id) ) {
            throw new Exception(
                'Klarte ikke å finne filmens ID fra URL',
                143001
            );
        }
        
        return intval($id);
    }
}