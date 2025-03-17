<?php

namespace UKMNorge\Arrangement\Kontaktperson;

use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Nettverk\Administrator;
use UKMNorge\Tools\Sanitizer;


class Kontaktperson implements KontaktInterface
{
    public $id = null;

    public $fornavn = null;
    public $etternavn = null;
    private $navn_backup = null;
    public $telefon = null;
    public $epost = null;

    public $tittel = null;
    public $facebook = null;
    public $bilde = null;

    public $adresse = null;
    public $postnummer = null;
    public $kommune_id = null;

    private $lastUpdated = null;
    private $system_locked = null;

    private $admin_id = null;

    public function __construct($id_or_row)
    {
        if (is_numeric($id_or_row)) {
            $this->_load_by_id($id_or_row);
        } elseif (is_array($id_or_row)) {
            $this->_load_by_row($id_or_row);
        } else {
            throw new Exception('KONTAKT: Oppretting av objekt krever numerisk id eller databaserad');
        }
    }

    /**
     * Hent kontaktpersonobjekt fra admin id
     *
     * @param Int $admin_id
     * @throws Exception
     * @return Kontaktperson
     */
    public static function getByAdminId( Int $admin_id ) {
        $query = new Query(
            self::getLoadQry().
            "WHERE `admin_id` = '#admin_id'",
            [
                'admin_id' => $admin_id
            ]
        );
        
        $res = $query->getArray();
        if( !$res) {
            throw new Exception(
                'Fant ingen kontaktperson-objekter for '. $admin_id,
                111001
            );
        }
        return new Kontaktperson( $res );        
    }

    private function _load_by_id($id)
    {
        $qry = new Query(
            self::getLoadQry()
                . "WHERE `kontakt`.`id` = '#id'",
            array('id' => $id)
        );
        $res = $qry->run('array');
        if ($res) {
            $this->_load_by_row($res);
        } else {
            throw new Exception('KONTAKT: Fant ikke kontaktperson ' . $id);
        }
    }

    private function _load_by_row($row)
    {
        if (!is_array($row)) {
            throw new Exception('KONTAKT: _load_by_row krever dataarray!');
        }
        $this->id = $row['id'];
        $this->fornavn =  $row['fornavn'];
        $this->etternavn = $row['etternavn'];
        $this->navn_backup = ''; //$row['name'];
        $this->telefon = $row['mobil'];
        $this->epost = $row['epost'];
        $this->tittel = '';
        $this->facebook = '';
        $this->bilde = $row['profile_image_url'];
        $this->adresse = ''; //$row['adress'];
        $this->postnummer = ''; //$row['postalcode'];
        $this->kommune_id = $row['eier_omrade_id'];
        $this->eier_omrade_id = $row['eier_omrade_id'];
        $this->eier_omrade_type = $row['eier_omrade_type'];
        $this->last_updated = $row['modified_date'];
        $this->system_locked = $row['system_locked'];
        if (isset($row['beskrivelse'])) {
            $this->beskrivelse = $row['beskrivelse'];
        }
        $this->admin_id = is_null($row['admin_id']) ? null : (int) $row['admin_id'];
    }

    public static function getLoadQry()
    {
        return "SELECT * FROM `ukm_omrade_kontaktperson` AS `kontakt`";
    }

    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
    public function getId()
    {
        return $this->id;
    }

    public function getNavn()
    {
        if( empty($this->getFornavn()) && empty($this->getEtternavn())) {
            return $this->navn_backup;
        }
        return $this->getFornavn() . ' ' . $this->getEtternavn();
    }

    public function getFornavn()
    {
        return $this->fornavn;
    }
    public function setFornavn($fornavn)
    {
        $this->fornavn = Sanitizer::sanitizeNavn($fornavn);
        return $this;
    }

    public function getEtternavn()
    {
        return $this->etternavn;
    }
    public function setEtternavn($etternavn)
    {
        $this->etternavn = Sanitizer::sanitizeEtternavn($etternavn);
        return $this;
    }

    public function getTelefon()
    {
        return $this->telefon;
    }
    public function setTelefon($telefon)
    {
        $this->telefon = $telefon;
        return $this;
    }

    public function getEpost()
    {
        return $this->epost;
    }
    public function setEpost($epost)
    {
        $this->epost = $epost;
        return $this;
    }

    public function getTittel()
    {
        return $this->tittel;
    }
    public function setTittel($tittel)
    {
        $this->tittel = $tittel;
        return $this;
    }

    public function getFacebook()
    {
        return $this->facebook;
    }
    public function setFacebook($facebook)
    {
        $this->facebook = $facebook;
        return $this;
    }

    public function erLast()
    {
        return $this->isLocked();
    }
    public function isLocked()
    {
        return $this->getSystem_locked() == 'true';
    }
    public function getSystem_locked()
    {
        return $this->system_locked;
    }
    public function setSystem_locked($system_locked)
    {
        $this->system_locked = $system_locked;
        return $this;
    }

    public function getBilde()
    {
        // Hvis det finnes bilde koblet til Kontaktperson, hent det
        if($this->bilde) {
            return $this->bilde;
        }
        // Hvis det ikke finnes bilde på Kontaktperson, sjekk User for bilde
        else if($this->getAdminId() != null) {
            $admin = new Administrator($this->getAdminId());
            $user = $admin->getUser();
            return $user->getBilde(); 
        }
        return $this->bilde;
    }
    public function setBilde($bilde)
    {
        $this->bilde = $bilde;
        return $this;
    }

    public function getAdresse()
    {
        return $this->adresse;
    }
    public function setAdresse($adresse)
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getPostnummer()
    {
        return $this->postnummer;
    }
    public function setPostnummer($postnummer)
    {
        $this->postnummer = $postnummer;
        return $this;
    }

    public function getBeskrivelse()
    {
        return $this->beskrivelse;
    }
    public function setBeskrivelse($beskrivelse)
    {
        $this->beskrivelse = $beskrivelse;
        return $this;
    }

    /**
     * Hent hvilken administrator ID denne kontaktpersonen er tilknyttet
     * WP_user::ID
     *
     * @return Int
     */
    public function getAdminId() {
        return $this->admin_id;
    }

    /**
     * Relater denne kontaktpersonen til en administrator
     * WP_user::ID
     *
     * @param Int $admin_id
     * @return self
     */
    public function setAdminId( Int $admin_id ) {
        $this->admin_id = $admin_id;
        return $this;
    }

    /**
     * Sett kommune
     *
     * @param kommune_id
     * @return $this
     **/
    public function setKommune($kommune_id)
    {
        $this->kommune_id = $kommune_id;
        return $this;
    }
    /**
     * Hent kommune
     *
     * @return object $kommune
     **/
    public function getKommune()
    {
        if (null == $this->kommune) {
            $this->kommune = new Kommune($this->kommune_id);
        }
        return $this->kommune;
    }

    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }
    public function setLastUpdated($lastUpdated)
    {
        $this->lastUpdated = $lastUpdated;
        return $this;
    }

    public static function validateClass($object)
    {
        return is_object($object) && in_array(
            get_class($object),
            [
                'UKMNorge\Arrangement\Kontaktperson\Kontaktperson',
                'Kontaktperson',
                'kontakt_v2'
            ]
        );
    }
}
