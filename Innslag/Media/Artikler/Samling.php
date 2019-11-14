<?php

namespace UKMNorge\Innslag\Media\Artikler;

require_once('UKM/Autoloader.php');

use UKMNorge\Innslag\Media\Samling as MediaSamling;
use UKMNorge\Database\SQL\Query;
use Exception;

class Samling extends MediaSamling
{
    var $innslag_id = false;

    /**
     * Last inn alle artikler for innslaget
     *
     * @return void
     */
    public function _load()
    {
        $this->artikler = array();
        $SQL = new Query(
            Artikkel::getLoadQuery() . "
            WHERE `b_id` = '#innslag_id'
            AND `post_type` = 'post'",
            [
                'innslag_id' => $this->getInnslagId()
            ]
        );
        $res = $SQL->run();

        if (!$res) {
            return false;
        }
        while ($row = Query::fetch($res)) {
            $this->add(new Artikkel($row));
        }
    }
}
