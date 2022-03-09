<?php

namespace UKMNorge\UKMFestivalen\Overnatting;

use UKMNorge\Database\SQL\Query;

class OvernattingRom {
    private $id;
    private $type;
    private $kapasitet;
    private $tableName = 'ukm_festival_overnatting_rom';

    public function __construct(Int $id) {
        $this->id = $id;

        // Hent data fra database for dette romet
        $this->_load_from_db();
    }

    /**
     * Database-spørring
     *
     * @return void
     */
    public function getLoadQuery()
    {
        return "SELECT * FROM " . $this->tableName;
    }

    /**
     * Kjøre database kall
     *
     * @return void
     */
    private function _load_from_db() {
        $sql = new Query(
            $this->getLoadQuery() . "
            WHERE `id` = '#id'",
            [
                'id' => $this->id
            ]
        );
        $res = $sql->run('array');

        $this->_load_from_array($res);
    }

    /**
     * Last inn info fra et array
     *
     * @param Array $data
     * @return void
     */
    private function _load_from_array(array $res) {
        $this->type = $res['type'];
        $this->kapasitet = $res['kapasitet'];
    }

    /**
     * Hent id
     *
     * @return Int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Hent type
     *
     * @return String
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Hent hent kapasitet
     *
     * @return Int
     */
    public function getKapasitet() {
        return (int) $this->kapasitet;
    }
   
}
