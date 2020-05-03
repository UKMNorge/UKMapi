<?php

namespace UKMNorge\Some\Kanaler;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Kanaler extends Collection
{

    public $type;
    public $id;

    public function __construct(String $type, Int $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * Hent container type
     *
     * @return String
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Hent container id
     *
     * @return Int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Hent alle kanaler
     *
     * @return Kanaler
     */
    public function getAll()
    {
        $kanaler = new static('alle',0);
        $query = new Query(
            "SELECT * 
            FROM `#table`
            ORDER BY `navn` ASC",
            [
                'table' => Kanal::TABLE
            ]
        );
        $res = $query->run();

        while ($row = Query::fetch($res)) {
            $kanaler->add(new Kanal($row));
        }
    }

    /**
     * Hent informasjon om en gitt kanal 
     *
     * @param String $id
     * @return Kanal
     */
    public function getByID(String $id)
    {
        $query = new Query(
            "SELECT *
            FROM `#table`
            WHERE `id` = '#id'",
            [
                'table' => Kanal::TABLE,
                'id' => $id
            ]
        );
        $data = $query->getArray();

        if (!$data) {
            throw new Exception('Fant ikke kanal ' . $id);
        }

        return new Kanal($data);
    }
}
