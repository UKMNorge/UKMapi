<?php

namespace UKMNorge\Filmer;

use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;

class Avspilling
{

    static $count = [];

    /**
     * Lagre avspilling for gitt film
     *
     * @param Film $film
     * @return void
     */
    public static function play(Film $film)
    {
        $ins = new Insert('ukm_tv_plays');
        $ins->add('tv_id', $film->getId());
        $ins->add('interval', 0);
        $ins->add('ip', $_SERVER['REMOTE_ADDR']);
        $ins->run();
    }

    /**
     * Hent antall avspillinger for gitt film
     *
     * @param Film $film
     * @return Int antall avspillinger
     */
    public static function getAntall(Film $film)
    {
        if (!isset(static::$count[$film->getId()])) {
            $sql = new Query(
                "SELECT `plays`
                FROM `ukm_tv_plays_cache`
                WHERE `tv_id` = '#tvid'",
                [
                    'tvid' => $film->getId()
                ]
            );
            static::$count[$film->getId()] = $sql->getField();
        }
        return static::$count[$film->getId()];
    }
}
