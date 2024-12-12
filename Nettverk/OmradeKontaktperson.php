<?php

namespace UKMNorge\Nettverk;

use DateTime;

require_once('UKM/Autoloader.php');

class OmradeKontaktperson {
    private int $id;
    private string $fornavn;
    private string $etternavn;
    private string $mobil; // Unique to OmradeKontakperson
    private string $beskrivelse;
    private string $epost;
    private DateTime $created_date; // Read only - set by database
    private DateTime $modified_date; // Read only - edited by database
    private int $eier_omrade_id;
    private int $eier_omrade_type;

    public function __construct(array $row) {
        $this->id = $row['id'];
        $this->fornavn = $row['fornavn'];
        $this->etternavn = $row['etternavn'];
        $this->mobil = $row['mobil'];
        $this->beskrivelse = $row['beskrivelse'] == null ? '' : $row['beskrivelse'];
        $this->epost = $row['epost'];
        $this->eier_omrade_id = $row['eier_omrade_id'];
        $this->eier_omrade_type = $row['eier_omrade_type'];

        if( !empty($row['created_date']) ) {
            $this->created_date = new DateTime( $row['created_date'] );
        }
        if( !empty($row['modified_date']) ) {
            $this->modified_date = new DateTime( $row['modified_date'] );
        }
    }

    public function getId() {
        return $this->id;
    }

    public function setId(int $id) {
        $this->id = $id;
    }

    public function getFornavn() {
        return $this->fornavn;
    }

    public function setFornavn(string $fornavn) {
        $this->fornavn = $fornavn;
    }

    public function getEtternavn() {
        return $this->etternavn;
    }

    public function setEtternavn(string $etternavn) {
        $this->etternavn = $etternavn;
    }

    public function getNavn() {
        return $this->fornavn . ' ' . $this->etternavn;
    }

    public function getMobil() {
        return $this->mobil;
    }

    public function setMobil(string $mobil) {
        $this->mobil = $mobil;
    }

    public function getBeskrivelse() {
        return $this->beskrivelse;
    }

    public function setBeskrivelse(string $beskrivelse) {
        $this->beskrivelse = $beskrivelse;
    }

    public function getEpost() {
        return $this->epost;
    }

    public function setEpost(string $epost) {
        $this->epost = $epost;
    }
    
    // Read only
    public function getCreatedDate() {
        return $this->created_date;
    }

    // Read only
    public function getModifiedDate() {
        return $this->modified_date;
    }

    // Read only
    public function getEierOmradeId() {
        return $this->eier_omrade_id;
    }

    // Read only
    public function getEierOmradeType() {
        return $this->eier_omrade_type;
    }
}