<?php

namespace UKMNorge\Nettverk;

use UKMNorge\Arrangement\Kontaktperson\KontaktInterface;

use Exception;
use DateTime;

require_once('UKM/Autoloader.php');

class OmradeKontaktperson implements KontaktInterface {
    private int $id;
    private string $fornavn;
    private string $etternavn;
    private string|null $mobil; // Unique to OmradeKontakperson
    private string $beskrivelse;
    private string|null $epost; // Unique to OmradeKontaktperson
    private DateTime $created_date; // Read only - set by database
    private DateTime $modified_date; // Read only - edited by database
    private int $eier_omrade_id;
    private string $eier_omrade_type;
    private int|null $wp_user_id;
    private string|null $profile_image_url;

    public function __construct(array $row) {
        // Når vi oppretter en kontaktperson, må vi ha en identifikator. Når id er -1, er det en ny kontaktperson som skal opprettes
        if( $row['id'] != -1 && $row['mobil'] == null && $row['epost'] == null ) {
            throw new Exception('Mobilnummer eller epost brukes som identifikator er påkrevd for å opprette en kontaktperson');
        }

        $this->id = $row['id'];
        $this->fornavn = $row['fornavn'];
        $this->etternavn = $row['etternavn'];
        $this->mobil = $row['mobil'];
        $this->beskrivelse = $row['beskrivelse'] == null ? '' : $row['beskrivelse'];
        
        // Validate epost
        if( !filter_var($row['epost'], FILTER_VALIDATE_EMAIL) ) {
            $this->epost = null;
        }
        else {
            $this->epost = $row['epost'];
        }

        $this->eier_omrade_id = $row['eier_omrade_id'];
        $this->eier_omrade_type = $row['eier_omrade_type'];
        $this->wp_user_id = $row['wp_user_id'] ?? null;
        $this->profile_image_url = $row['profile_image_url'];

        if( !empty($row['created_date']) ) {
            $this->created_date = new DateTime( $row['created_date'] );
        }
        if( !empty($row['modified_date']) ) {
            $this->modified_date = new DateTime( $row['modified_date'] );
        }
    }

    public static function createEmpty() : OmradeKontaktperson {
        return new OmradeKontaktperson([
            'id' => -1,
            'fornavn' => '',
            'etternavn' => '',
            'mobil' => '',
            'epost' => '',
            'beskrivelse' => '',
            'eier_omrade_id' => -1,
            'eier_omrade_type' => '',
            'profile_image_url' => null,
        ]);
    }

    public function getArray() {
        return [
            'id' => $this->id,
            'fornavn' => $this->fornavn,
            'etternavn' => $this->etternavn,
            'mobil' => $this->mobil,
            'beskrivelse' => $this->beskrivelse,
            'epost' => $this->epost,
            'created_date' => $this->created_date->format('Y-m-d H:i:s'),
            'modified_date' => $this->modified_date->format('Y-m-d H:i:s'),
            'eier_omrade_id' => $this->eier_omrade_id,
            'eier_omrade_type' => $this->eier_omrade_type,
            'profile_image_url' => $this->profile_image_url,
            'wp_user_id' => $this->wp_user_id,
        ];
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

    public function hasValidMobil() : bool {
        return preg_match('/^\d{8}$/', $this->mobil) == 1;
    }

    public function hasValidEpost() : bool {
        return filter_var($this->epost, FILTER_VALIDATE_EMAIL);
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

    public function setBeskrivelse(string|null $beskrivelse) {
        $this->beskrivelse = $beskrivelse ?? '';
    }

    public function getEpost() {
        return $this->epost;
    }

    public function setEpost(string|null $epost) {
        $this->epost = $epost ?? '';
    }

    public function getWpUserId() {
        return $this->wp_user_id;
    }
    
    public function setWpUserId(int $wp_user_id) {
        $this->wp_user_id = $wp_user_id;
    }

    public function getProfileImageUrl() {
        return $this->profile_image_url;
    }

    public function hasProfileImage() {
        return !is_null($this->profile_image_url) && $this->profile_image_url != '';
    }

    public function setProfileImageUrl(string|null $url) {
        $this->profile_image_url = $url;
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

    // Interfacing 
    public function getTittel() {
        return '';
    }

    public function getTelefon() {
        return $this->getMobil();
    }

    public function getFacebook() {
        return '';
    }

    public function getId() {
        return $this->id;
    }

    public function getBilde() {
        return $this->getProfileImageUrl();
    }
}