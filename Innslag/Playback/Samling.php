<?php

namespace UKMNorge\Innslag\Playback;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class Samling extends Collection
{
    var $id = null;
    var $filer = null;
    var $innslag_id = null;


    /**
     * Opprett en samling Playback-filer
     *
     * @param Int $innslagID
     */
    public function __construct(Int $innslagID)
    {
        $this->innslag_id = $innslagID;
    }

    /**
     * Last inn alle playbackfiler for gitt innslag
     *
     * @return void
     */
    public function _load()
    {
        $sql = new Query(
            Playback::getLoadQuery() . "
						WHERE `b_id` = '#innslag'",
            [
                'innslag' => $this->getInnslagID()
            ]
        );
        $res = $sql->run();
        if ($res) {
            while ($row = Query::fetch($res)) {
                $this->add(new Playback($row));
            }
        }
    }


    /**
     * Hent ID av eier-innslag
     */
    public function getInnslagID()
    {
        return $this->innslag_id;
    }

    /**
     * Hent gitt playbackfil fra ID
     *
     * @param Int $id
     * @throws Exception
     * @return Playback
     */
    public static function getById(Int $id) {
        $sql = new Query(
            Playback::getLoadQuery() . "
						WHERE `pb_id` = '#playbackid'",
            [
                'playbackid' => $id
            ]
        );
        $res = $sql->getArray();
        if ($res) {
            return new Playback($res);
        }
        throw new Exception(
            'Kunne ikke hente mediefil',
            133001
        );
    }
}
