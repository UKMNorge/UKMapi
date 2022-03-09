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
    private $rom = null;
    private $tableName = 'ukm_festival_overnatting_rel_person_rom';


    public function __construct(Int $id, String $navn, String $mobil, String $epost, String $ankomst, String $avreise) {
        $this->id = $id;
        $this->navn = $navn;
        $this->mobil = $mobil;
        $this->epost = $epost;
        $this->ankomst = $ankomst;
        $this->avreise = $avreise;
        
        // Hent rom (OvernattingRom) som personen er i (skal sove i)
        $this->_load_rom_from_db();
    }

    /**
     * Kjøre database kall
     *
     * @return void
     */
    private function _load_rom_from_db() {
        $sql = new Query(
            "SELECT `rom_id` FROM " . $this->tableName . "
            WHERE `person_id` = '#personId'",
            [
                'personId' => $this->id
            ]
        );

        $res = $sql->run('array');

        // Opprett OvernattingRom bare hvis det finnes en relasjon
        if($res != null) {
            $this->rom = new OvernattingRom((int) $res['rom_id']);
        } 
    }

    /**
     * Hent rom
     * 
     * OBS: Den kan returnere null hvis det ikke er et rom assosiert med personen
     *
     * @return OvernattingRom
     */
    public function getRom() {
        return $this->rom;
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
     * Hent mobil nummer
     *
     * @return String
     */
    public function getMobil() {
        return $this->mobil;
    }

    /**
     * Hent epost
     *
     * @return String
     */
    public function getEpost() {
        return $this->epost;
    }

    /**
     * Hent ankomst
     *
     * @return String
     */
    public function getAnkomst() {
        return $this->ankomst;
    }

    /**
     * Hent avreise
     *
     * @return String
     */
    public function getAvreise() {
        return $this->avreise;
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
