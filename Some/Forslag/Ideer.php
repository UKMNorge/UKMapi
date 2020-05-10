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
     * Hent alle ideer i systemet
     *
     * @return Ideer
     */
    public static function loadAll() {
        $query = new Query(
            Ide::getLoadQuery() . "
            ORDER BY `publisering` ASC, `#table_ide`.`id` ASC",
            [
                'table_ide' => Ide::TABLE
            ]
        );
        $res = $query->run();

        $ideer = new static();

        while( $data = Query::fetch($res)) {
            $ideer->add( new Ide($data));
        }
        return $ideer;
    }

    /**
     * Last inn alle ideer til collection
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
