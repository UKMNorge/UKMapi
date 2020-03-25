<?php

namespace UKMNorge\Filmer\UKMTV\Server;

use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Meta\Collection;
use UKMNorge\Meta\ParentObject;
use UKMNorge\Meta\Write;

class BandwidthMode
{
    const MODES = ['low', 'normal'];
    static $meta;

    /**
     * Hvilken bandwidth skal vi bruke for øyeblikket?
     *
     * @return String 'low'|'normal'
     */
    public static function getMode()
    {
        return static::getMeta()->get('bandwidthmode')->getValue();
    }

    /**
     * Opererer vi med standard båndbredde?
     *
     * @return Bool
     */
    public static function erNormal()
    {
        return static::getMode() == 'normal';
    }

    /**
     * Skal UKM-TV operere i sparemodus?
     *
     * @return Bool
     */
    public static function erSparemodus()
    {
        return static::getMode() == 'low';
    }

    /**
     * Sett hvilken bandwidthmode UKM-TV skal operere i
     *
     * @param String $mode
     * @throws Exception
     * @return bool
     */
    public static function setMode(String $mode)
    {
        if (!in_array($mode, static::MODES)) {
            throw new Exception(
                $mode . ' er ikke støttet BandwidthMode',
                543001
            );
        }

        if ($mode == 'normal') {
            $value = static::getMeta()->get('bandwidthmode');
            Write::delete($value);
        } else {
            $value = static::getMeta()->get('bandwidthmode')->set('low');
            Write::set($value);
        }
        static::getMeta()->set($value);
        return true;
    }

    /**
     * Hent meta-collection for UKM-TV
     *
     * @return Collection
     */
    public static function getMeta()
    {
        if (static::$meta == null) {
            static::$meta = new Collection(
                new ParentObject('UKMTV', 0)
            );
        }
        return static::$meta;
    }
}
