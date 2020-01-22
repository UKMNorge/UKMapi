<?php

namespace UKMNorge\Filmer\UKMTV\Server;

use UKMNorge\Database\SQL\Query;

class BandwidthMode {
    static $mode = null;

    /**
     * Hvilken bandwidth skal vi bruke for øyeblikket?
     *
     * @return String 'low'|'normal'
     */
    public static function getMode() {
        static::_load();
        return static::$mode;
    }

    /**
     * Opererer vi med standard båndbredde?
     *
     * @return Bool
     */
    public static function erNormal() {
        return static::getMode() == 'normal';
    }

    /**
     * Skal UKM-TV operere i sparemodus?
     *
     * @return Bool
     */
    public static function erSparemodus() {
        return static::getMode() == 'low';
    }

    /**
     * Last inn config fra databasen
     *
     * @return void
     */
    private static function _load() {
        if( static::$mode == null ) {
            $query = new Query("SELECT `conf_value`
                            FROM `ukm_tv_config`
                            WHERE `conf_name` = 'bandwidth_mode'");
            static::$mode = $query->getField() == 'low' ? 'low' : 'normal';
        }
    }
}