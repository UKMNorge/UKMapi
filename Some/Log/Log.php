<?php

namespace UKMNorge\Some\Log;

use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Log extends Collection
{
    public $objekt_id;
    public $objekt_type;

    /**
     * Opprett ny logg-samling med events
     *
     * @param String $objekt_type
     * @param Int $objekt_id
     */
    public function __construct(String $objekt_type, Int $objekt_id)
    {
        $this->objekt_type = $objekt_type;
        $this->objekt_id = $objekt_id;
    }

    /**
     * Hent gitt event fra database-id
     *
     * @param Int $db_auto_increment_id
     * @return Event
     */
    public static function getByDbId(Int $db_auto_increment_id)
    {
        $query = new Query(
            "SELECT *
            FROM `#table`
            WHERE `id` = '#id'",
            [
                'table' => Event::TABLE,
                'id' => $db_auto_increment_id
            ]
        );

        return new Event($query->getArray());
    }

    /**
     * Last inn alle event for denne samlingen
     *
     * @return Log
     */
    public function _load()
    {
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `objekt_type` = '#objekt_type'
            AND `objekt_id` = '#objekt_id'
            ORDER BY `id` ASC",
            [
                'table' => Event::TABLE,
                'objekt_type' => $this->objekt_type,
                'objekt_id' => $this->objekt_id
            ]
        );

        $res = $query->run();

        while ($data = Query::fetch($res)) {
            $this->add(new Event($data));
        }
    }
}
