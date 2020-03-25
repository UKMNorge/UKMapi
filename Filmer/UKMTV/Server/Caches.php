<?php

namespace UKMNorge\Filmer\UKMTV\Server;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;

class Caches extends Collection
{
    const THRESHOLD_FOR_DELETE = 4; // int:minutes

    static $har_aktiv;
    static $aktive;
    static $inaktive;
    /**
     * Hent ut en tilfeldig aktiv cache
     *
     * @throws Exception
     * @return Cache
     */
    public function getRandomActiveCache()
    {
        $sql = new Query(
            "SELECT *
            FROM `ukm_tv_caches_caches`
            WHERE `last_heartbeat` >= NOW() - INTERVAL 3 MINUTE
                AND `status` = 'ok' AND `deactivated` = 0
            ORDER BY RAND()
            LIMIT 1"
        );
        $data = $sql->getArray();

        if (!$data) {
            throw new Exception(
                'Fant ingen aktive cacher',
                143002
            );
        }

        return new Cache($data);
    }

    /**
     * Aktiver gitt cache
     *
     * @param Cache $cache
     * @return Bool
     */
    public static function aktiverCache(Cache $cache)
    {
        $SQL = new Update(
            'ukm_tv_caches_caches',
            [
                'id' => $cache->getId()
            ]
        );
        $SQL->add('deactivated', 0);
        $SQL->run();

        return true;
    }

    /**
     * Deaktiver gitt cache
     *
     * @param Cache $cache
     * @return Bool
     */
    public static function deaktiverCache(Cache $cache)
    {
        $SQL = new Update(
            'ukm_tv_caches_caches',
            [
                'id' => $cache->getId()
            ]
        );
        $SQL->add('deactivated', 1);
        $SQL->run();
        return true;
    }

    /**
     * Hent alle aktive cacher
     *
     * @return Caches
     */
    public static function getAllActive()
    {
        if (is_null(static::$inaktive)) {
            $sql = new Query(
                "SELECT *
                FROM `ukm_tv_caches_caches`
                WHERE `deactivated` = 0
                ORDER BY `last_heartbeat` ASC"
            );
            $res = $sql->getResults();

            static::$inaktive = new Caches();
            while ($row = Query::fetch($res)) {
                static::$inaktive->add(new Cache($row));
            }
        }
        return static::$inaktive;
    }

    /**
     * Finnes det minst en aktiv cache?
     *
     * @return Bool
     */
    public static function harAktivCache() {
        if( is_null(static::$har_aktiv) ) {
            $sql = new Query(
                "SELECT `id`
                FROM `ukm_tv_caches_caches`
                WHERE `deactivated` = 0
                LIMIT 1"
            );
            $res = $sql->run();
            static::$har_aktiv = Query::numRows($res);
        }
        return static::$har_aktiv;
    }

    /**
     * Hent en cache fra gitt ID
     *
     * @param Int $id
     * @throws Exception
     * @return Cache
     */
    public static function getById( Int $id ) {
        $sql = new Query(
            "SELECT *
            FROM `ukm_tv_caches_caches`
            WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );
        $data = $sql->getArray();
        
        if( !$data ) {
            throw new Exception(
                'Fant ikke cache '. $id,
                143003
            );
        }
        return new Cache( $data );
    }

    /**
     * Hent alle cacher
     *
     * @return Caches
     */
    public static function getAllInkludertInaktive()
    {
        if (is_null(static::$inaktive)) {
            $sql = new Query(
                "SELECT *
                FROM `ukm_tv_caches_caches`
                ORDER BY `last_heartbeat` ASC"
            );
            $res = $sql->getResults();

            static::$inaktive = new Caches();
            while ($row = Query::fetch($res)) {
                static::$inaktive->add(new Cache($row));
            }
        }
        return static::$inaktive;
    }

    /**
     * Slett alle inaktive cacher
     *
     * @return Array
     */
    public static function deleteInactiveCaches()
    {
        $time = time() - (static::THRESHOLD_FOR_DELETE * 60); # Two minutes from now
        
        $caches = new Query(
            "SELECT * 
            FROM `ukm_tv_caches_caches` 
            WHERE UNIX_TIMESTAMP(`last_heartbeat`) < '#time'
            AND `deactivated` = 1
            AND `id` > 1",
            [
                'time' => $time
            ]
        );
        $res = $caches->run();

        if (!$res) {
            throw new Exception(
                'Klarte ikke å hente cacher fra serveren',
                543002
            );
        }
        
        $success = [];
        $errors = [];
        while ($cache = Query::fetch($res)) {
            $del = new Delete(
                'ukm_tv_caches_caches',
                [
                    'id' => $cache['id']
                ]
            );
            $res2 = $del->run();

            if (!$res2) {
                $errors[] = $cache['id'];
            } else {
                $success[] = $cache['id'];
            }
        }

        return [
            'success' => $success,
            'error' => $errors
        ];
    }
}
