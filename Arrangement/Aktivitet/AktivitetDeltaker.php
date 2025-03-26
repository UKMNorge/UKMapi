<?php

namespace UKMNorge\Arrangement\Aktivitet;

use UKMNorge\Database\SQL\Query;

use Exception;

class AktivitetDeltaker {
    public final static $table = 'aktivitet_deltaker';

    private int $mobil;
    private int $aktivitetTidspunktId;
    private bool $aktiv;

    // Pga avhengighet til tidspunkt kan kun hentes via aktivitet_deltakelse tabell og ikke ved bruk av ID.
    public function __construct($row) {
        $this->_load_by_row($row);
    }

    public function getId() {
        return $this->mobil;
    }

    public function getAktivitetTidspunktId() {
        return $this->aktivitetTidspunktId;
    }

    public function getMobil() {
        return $this->mobil;
    }
    
    public function erAktiv() {
        return $this->aktiv;
    }

    public function getAktivitedTidspunkt() {
        return new AktivitetTidspunkt($this->aktivitetTidspunktId);
    }

    public function _load_by_row($row) {
        $this->mobil = $row['mobil'];
        $this->aktivitetTidspunktId = $row['tidspunkt_id'];
        $this->aktiv = $row['aktiv'];
    }

}