<?php

namespace UKMNorge\Some\Forslag;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Some\Kanaler\Kanal;

class Ideer extends Collection
{

    /**
     * Hent ide med gitt id
     *
     * @param Int $id
     * @return Ide
     */
    public static function getById(Int $id)
    {
        $query = new Query(
            Ide::getLoadQuery() . "
            WHERE `#table`.`id` = '#id'",
            [
                'table' => Ide::TABLE,
                'id' => $id
            ]
        );

        $data = $query->getArray();

        if (!$data) {
            throw new Exception('Fant ikke idé ' . $id);
        }

        return new Ide($data);
    }

    /**
     * Last inn alle ideer
     *
     * @return void
     */
    public function _load()
    {
        $query = new Query(
            Ide::getLoadQuery() . " ORDER BY `#table`.`id` DESC",
            [
                'table' => Ide::TABLE
            ]
        );
        $res = $query->run();

        while ($data = Query::fetch($res)) {
            $this->add(new Ide($data));
        }
    }
}
