<?php

namespace UKMNorge\Some\Forslag;

use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Tekster extends Collection
{

    public $objekt_type;
    public $objekt_id;

    /**
     * Opprett en samling av tekster
     *
     * @param String $type
     * @param Int $objekt_id
     * @return Tekster
     */
    public function __construct(String $type, Int $objekt_id)
    {
        $this->objekt_type = $type;
        $this->objekt_id = $objekt_id;
    }

    /**
     * Hent en tekst fra database-id
     *
     * @param Int $id
     * @return Tekst
     */
    public static function getById(Int $id)
    {
        $query = new Query(
            "SELECT * 
            FROM `#table`
            WHERE `id` = '#id'",
            [
                'table' => Tekst::TABLE,
                'id' => $id
            ]
        );

        return new Tekst($query->getArray());
    }

    /**
     * Last inn alle tekster for et objekt (ide|status)
     *
     * @return void
     */
    public function _load()
    {
        $query = false;
        switch ($this->type) {
            case 'ide':
                $query = new Query(
                    "SELECT * 
                    FROM `#table`
                    WHERE `objekt_type` = '#objekttype'
                    AND `objekt_id` = '#objektid'",
                    [
                        'table' => Tekst::TABLE,
                        'objekt_type' => $this->objekt_type,
                        'objekt_id' => $this->objekt_id
                    ]
                );
                break;
        }

        if ($query) {
            $res = $query->run();

            while ($data = Query::fetch($res)) {
                $this->add(new Tekst($data));
            }
        }
    }
}
