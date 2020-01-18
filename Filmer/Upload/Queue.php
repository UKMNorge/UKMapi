<?php

namespace UKMNorge\Filmer\Upload;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Innslag;

class Queue extends Collection
{

    /**
     * Hent alle filmer som står i kø for dette innslaget
     *
     * @param Innslag $innslag
     * @return Queue
     */
    public static function getByInnslag(Innslag $innslag)
    {
        return static::_loadByQuery(
            new Query(
                static::getLoadQuery() .
                    "AND `innslag_id` = '#innslagid'
                    ORDER BY `id` ASC",
                [
                    'innslagid' => $innslag->getId()
                ]
            )
        );
    }

    /**
     * Hent alle filmer som står i kø for dette arrangmentet
     *
     * @param Arrangement $arrangement
     * @return Queue
     */
    public static function getByArrangement(Arrangement $arrangement)
    {
        return static::_loadByQuery(
            new Query(
                static::getLoadQuery() .
                    "AND `arrangement_id` = '#arrangementid'
                    ORDER BY `id` ASC",
                [
                    'arrangementid' => $arrangement->getId()
                ]
            )
        );
    }

    /**
     * Hent start av SQL-spørringen. OBS: where er satt!
     *
     * @return String SQL query
     */
    public static function getLoadQuery()
    {
        return "SELECT `cron_id` 
        FROM `ukm_uploaded_video`
        WHERE `converted` = 'false'";
    }

    /**
     * Last inn alle filmer som queriet returnerer
     *
     * @param Query $query
     * @return Queue
     */
    private static function _loadByQuery(Query $query)
    {
        $queue = new Queue();
        $res = $query->run();
        while ($row = Query::fetch($res)) {
            $queue->add(new Film(intval($row['cron_id'])));
        }
        return $queue;
    }
}
