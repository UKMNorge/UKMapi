<?php

namespace UKMNorge\Filmer\UKMTV;

use UKMNorge\Collection;
use Exception;
use UKMNorge\Database\SQL\Query;

class Filmer extends Collection
{
    public $query;
    /**
     * Henter inn filmer basert på gitt spørring (via constructor)
     *
     * @return bool true
     * @throws Exception
     */
    public function _load()
    {
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

        return true;
    }

    /**
     * Opprett en ny samling filmer
     *
     * @param Query Spørring for å hente ut filmer
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    /**
     * Hent gitt film fra ID
     *
     * @param Int $tv_id
     * @return Film
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
        if (!$data) {
            throw new Exception(
                'Beklager! Klarte ikke å finne film ' . intval($tv_id),
                115007
            );
        }
        return new Film($data);
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
        return new Filmer($query);
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
            GROUP BY `ukm_tv_files`.`tv_id`",
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
     * Finnes det filmer med disse to tag'ene?
     *
     * @param String $tag1
     * @param Int $tagId1
     * @param String $tag2
     * @param Int $tagId2
     * @return void
     */
    public static function harTagsFilmer(String $tag1, Int $tagId1, String $tag2, Int $tagId2)
    {
        $query = new Query(
            static::_getTagQuery() . " LIMIT 1",
            [
                'tagNameOne' => $tag1,
                'tagValueOne' => $tagId1,
                'tagNameTwo' => $tag2,
                'tagValueTwo' => $tagId2
            ]
        );
        return !!$query->getField(); # (dobbel nekting er riktig)        
    }

    public static function getByTags(String $tag1, Int $tagId1, String $tag2, Int $tagId2)
    {
        return new Filmer(
            new Query(
                static::_getTagQuery(),
                [
                    'tagNameOne' => $tag1,
                    'tagValueOne' => $tagId1,
                    'tagNameTwo' => $tag2,
                    'tagValueTwo' => $tagId2
                ]
            )
        );
    }


    /**
     * Undocumented function
     *
     * @return void
     */
    private static function _getTagQuery()
    {
        return "SELECT *
            FROM `ukm_tv_files` 
            JOIN `ukm_tv_tags` AS `tag1`
                ON (`ukm_tv_files`.`tv_id` = `tag1`.`tv_id` AND `tag1`.`type`='#tagNameOne' AND `tag1`.`foreign_id` = #tagValueOne)
            JOIN `ukm_tv_tags` AS `tag2`
                ON (`ukm_tv_files`.`tv_id` = `tag2`.`tv_id` AND `tag2`.`type`='#tagNameTwo' AND `tag2`.`foreign_id` = #tagValueTwo)";
    }
}
