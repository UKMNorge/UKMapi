<?php

namespace UKMNorge\Some\Kanaler;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Some\Forslag\Ide;

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
    public function getType()
    {
        return $this->type;
    }

    /**
     * Hent container id
     *
     * @return Int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent alle kanaler
     *
     * @return Kanaler
     */
    public static function getAlle()
    {
        return new static('alle', 0);
    }

    public function _load()
    {
        switch ($this->getType()) {
            case 'ide':
                $this->addFromQuery(
                    new Query(
                        "SELECT * 
                    FROM `#table`
                    LEFT JOIN `#rel`
                        ON (`#rel`.`kanal_id` = `#table`.`id` AND `#rel`.`ide_id` = '#ide_id')
                    ORDER BY `navn` ASC",
                        [
                            'rel' => Ide::TABLE_REL_KANAL,
                            'table' => Kanal::TABLE,
                            'ide_id' => $this->getId()
                        ]
                    )
                );
                break;

            case 'alle':
                $this->addFromQuery(
                    new Query(
                        "SELECT * 
                    FROM `#table`
                    ORDER BY `navn` ASC",
                        [
                            'table' => Kanal::TABLE
                        ]
                    )
                );
                break;
        }
    }

    private function addFromQuery(Query $query)
    {
        $res = $query->run();

        while ($row = Query::fetch($res)) {
            $this->add(new Kanal($row));
        }

        return true;
    }

    /**
     * Hent informasjon om en gitt kanal 
     *
     * @param String $id
     * @return Kanal
     */
    public static function getById(String $id)
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
