<?php

namespace UKMNorge\Innslag\Playback;

use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class Samling extends Collection
{
    var $id = null;
    var $filer = null;
    var $loaded = false;
    var $innslagID = null;


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
     * Last inn og returner alle playbackfiler for innslaget
     *
     * @return void
     */
    public function getAll()
    {
        if (!$this->loaded) {
            $this->_load();
        }
        return parent::getAll();
    }

    /**
     * Last inn alle playbackfiler for gitt innslag
     *
     * @return void
     */
    private function _load()
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
            while ($row = SQL::fetch($res)) {
                $this->add(new Playback($row));
            }
        }
        $this->loaded = true;
    }


    /**
     * Hent ID av eier-innslag
     */
    public function getInnslagID()
    {
        return $this->innslagID;
    }
}
