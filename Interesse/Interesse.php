<?php

namespace UKMNorge\Interesse;

use Exception;

require_once('UKM/Autoloader.php');


class Interesse {
    private int $id;
    private string $navn;
    private string $beskrivelse;
    private string $epost;
    private string $mobil;
    private bool $arrangorInteresse;
    private array $kommuner = [];


    public function __construct($id, $navn, $beskrivelse, $epost, $mobil, $arrangorInteresse, $kommuner = []) {
        $this->id = $id;
        $this->navn = $navn;
        $this->beskrivelse = $beskrivelse;
        $this->epost = $epost;
        $this->mobil = $mobil;
        $this->arrangorInteresse = $arrangorInteresse;
        $this->kommuner = $kommuner;
    }

    public function getId() : int {
        return $this->id;
    }

    public function getNavn() : string {
        return $this->navn;
    }

    public function getBeskrivelse() : string {
        return $this->beskrivelse;
    }

    public function getEpost() : string {
        return $this->epost;
    }

    public function getMobil() : string {
        return $this->mobil;
    }

    public function isArrangorInteresse() : bool {
        return $this->arrangorInteresse;
    }

    public function getKommuner() : array /* of int */ {
        return array_map('intval', $this->kommuner);
    }
}