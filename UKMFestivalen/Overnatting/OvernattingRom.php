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

        $this->_load_from_db();
    }

    private function _load_from_db() {
        $sql = new Query(
            "SELECT * FROM " . $this->tableName . "
            WHERE `id` = '#id'",
            [
                'id' => $this->id
            ]
        );
        $res = $sql->run('array');

        
        echo ' --- ';
        var_dump($this->id);
        echo ' --- ';
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
     * @return void
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
        return $this->navn;
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
