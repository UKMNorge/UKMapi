<?php

namespace UKMNorge\Filmer\UKMTV;

use UKMNorge\Collection;
use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Filmer\UKMTV\FilmInterface;


class Filmer extends Collection
{

    var $is_empty = false;

    public $query;
    // Query used after migration to CloudFlare Stream
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
        
        $res = $this->query->run();
        if (!$res) {
            throw new Exception(
                'Kunne ikke laste inn filmer, grunnet databasefeil',
                115001
            );
        }
        while ($filmData = Query::fetch($res)) {
            $film = new Film($filmData);
            if ($film->erSlettet()) {
                continue;
            }
            $this->add($film);
        }

        // Legg til filmer fra CloudFlare Stream. 2023 migrerte vi filmene våre til CloudFlare Stream
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
     * @param Query Spørring for å hente ut filmer fra CloudFlare Stream database
     */
    public function __construct(Query $query, Query $cfQuery=null)
    {
        $this->query = $query;
        $this->cfQuery = $cfQuery;
    }

    /**
     * Hent gitt film fra ID
     *
     * @param Int $tv_id - id som representerer en film. Kan være id på Film eller CloudflareFilm
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
        
        // Hvis det ikke finnes film på gamle database, sjekk på Cloudflare DB
        if(!$data) {
            $query2 = new Query(
                CloudflareFilm::getLoadQuery() . "
                WHERE `id` = '#tvid'
                AND `deleted` = 'false'",
                [
                    'tvid' => $tv_id
                ]
            );
            $data2 = $query2->getArray();
        }

        if (!$data && !$data2) {
            throw new Exception(
                'Beklager! Klarte ikke å finne film ' . intval($tv_id),
                115007
            );
        }
        return $data ? new Film($data) : new CloudflareFilm($data2);
    }

    /**
     * Opprett en filmerCollection for gitt innslagId
     *
     * @param Int $innslagId
     * @return Filmer
     */
    public static function getByInnslag(Int $innslagId)
    {
        $query = new Query(
            Film::getLoadQuery() . "
            WHERE `b_id` = '#innslagId'
            AND `tv_deleted` = 'false'", // deleted ikke nødvendig, men gjør lasting marginalt raskere
            [
                'innslagId' => $innslagId
            ]
        );

        $queryCF = new Query(
            CloudflareFilm::getLoadQuery() . "
            WHERE `innslag` = '#innslagId'
            AND `deleted` = 'false'", // deleted ikke nødvendig, men gjør lasting marginalt raskere
            [
                'innslagId' => $innslagId
            ]
        );

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
        return new Filmer($query);
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

        return !!$query->getField() || !!static::getQueryTagsCF($tags, 1)->getField(); # (dobbel nekting er riktig)        
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
            static::getQueryTagsCF($tags)
        );
    }

    /**
     * Hent query som passer for cloudflare tags
     *
     * @param Tags[]
     * @return Query
     */
    private function getQueryTagsCF(array $tags, $limit=null) {
        if(sizeof($tags) < 1) {
            throw new Exception("Det må komme minst en tag for å generere query");
        }

        $tagsArr = [];
        $tagsQuery = "WHERE ";

        foreach($tags as $i => $tag) {
            if($i > 0 && $i < sizeof($tags)) {
                $tagsQuery .= ' AND ';
            }

            // Sjekk om tagen støttes av Cloudflare
            if(static::erTagGyldigICF($tag)) {
                throw new Exception('Tag støttes ikke av CF Filmer');
            }

            // Det er liste av tags
            if(is_array($tag->getValue())) {
                $innerTags = $tag->getValue();
                $tagsQuery .= '(';
                
                foreach($innerTags as $j => $valInTag) {
                    $tagsQuery .= $tag->getId() . "='#" . $tag->getId() . $j  . "'";
                    $tagsQuery .= $j+1 < sizeof($innerTags) ? ' OR ' : '';
                    $tagsArr[$tag->getId().$j] = $valInTag;
                }

                $tagsQuery .= ')';
            }
            // Det er single tag
            else {
                $tagsQuery .= $tag->getId() . "='#" . $tag->getId() . "'";
                $tagsArr[$tag->getId()] = $tag->getValue();
            }

        }

        $limitStr = $limit ? ' LIMIT ' . $limit : '';

        $cloudFlareQuery = new Query(
            CloudflareFilm::getLoadQuery() .
            $tagsQuery . $limitStr,
            $tagsArr
        );

        return $cloudFlareQuery;
    }

    /**
     * Sjekk om Tag er gyldig for Cloudflare Database
     *
     * @param String $search_string
     * @return Filmer
     */
    private static function erTagGyldigICF($tag) {
        return !in_array($tag->getId(), ["arrangement", "arrangement_type", "fylke", "innslag", "kommune", "person", "sesong"]);
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

        // SEARCH FOR PERSONS NAME
        $qry = new Query(
            "SELECT `p`.`tv_id`, `p_name`,
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
            )
        );
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
