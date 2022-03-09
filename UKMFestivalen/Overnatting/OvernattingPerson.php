<?php

namespace UKMNorge\UKMFestivalen\Overnatting;
use UKMNorge\Database\SQL\Query;


class OvernattingPerson {
    private $id;
    private $navn;
    private $mobil;
    private $epost;
    private $ankomst;
    private $avreise;
    private $rom;
    private $tableName = 'ukm_festival_overnatting_rel_person_rom';


    public function __construct(Int $id, String $navn, String $mobil, String $epost, String $ankomst, String $avreise) {
        $this->id = $id;
        $this->navn = $navn;
        $this->mobil = $mobil;
        $this->epost = $epost;
        $this->ankomst = $ankomst;
        $this->avreise = $avreise;
        
        $this->_load_rom_from_db();
    }

    private function _load_rom_from_db() {
        $sql = new Query(
            "SELECT `rom_id` FROM " . $this->tableName . "
            WHERE `person_id` = '#personId'",
            [
                'personId' => $this->id
            ]
        );

        $res = $sql->run('array');

        if($res) {
            $this->rom = new OvernattingRom((int) $res['rom_id']);

        }
            
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
     * hent mobil nummer
     *
     * @return String
     */
    public function getMobil() {
        return $this->mobil;
    }

    /**
     * hent epost
     *
     * @return String
     */
    public function getEpost() {
        return $this->epost;
    }

    /**
     * hent ankomst
     *
     * @return String
     */
    public function getAnkomst() {
        return $this->ankomst;
    }

        /**
     * hent avreise
     *
     * @return String
     */
    public function getAvreise() {
        return $this->avreise;
    }

    /**
     * Set navn
     *
     * @return void
     */
    public function setNavn($navn) {
        $this->navn = $navn;
    }

    /**
     * Hent navn
     *
     * @return String
     */
    public function getNavn() {
        return $this->navn;
    }
   
}
