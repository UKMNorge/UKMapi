<?php

namespace UKMNorge\Arrangement\Aktivitet;

use ElementorPro\Modules\Forms\Fields\Date;
use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Program\Hendelse;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Nettverk\Administrator;
use UKMNorge\Tools\Sanitizer;

use DateTime;

class AktivitetTidspunkt {
    public const TABLE = 'aktivitet_tidspunkt';
    
    private int $tidspunktId;
    private string $sted;
    private DateTime $start;
    private DateTime $slutt;
    private int $varighetMinutter;
    private int $maksAntall;
    private bool $harPaamelding;
    private bool $erSammeStedSomAktivitet;
    private bool $kunInterne;

    private $deltakere = null;

    private int $aktivitetId; // Foreign key til Aktivitet
    private int|null $hendelseId; // Foreign key til Hendelse. Kan være null.


    public function __construct($id_or_row) {
        if (is_numeric($id_or_row)) {
            $this->_load_by_id($id_or_row);
        } elseif (is_array($id_or_row)) {
            $this->_load_by_row($id_or_row);
        } else {
            throw new Exception('AktivitetTidspunkt: Oppretting av objekt krever numerisk id eller databaserad');
        }
    }

    public static function getAllByHendelse($hendelseId) {
        $query = new Query(
            "SELECT DISTINCT * from ". AktivitetTidspunkt::TABLE ."
            WHERE `c_id` = '#hendelseId'",
            [
                'hendelseId' => $hendelseId            
            ]
        );

        $res = $query->run();

        $tags = [];
        while ($row = Query::fetch($res)) {
            $tags[] = new AktivitetTidspunkt($row);
        }

        return $tags;
    }

    public function getId() {
        return $this->tidspunktId;
    }

    public function getSted() {
        return $this->sted;
    }

    public function getStart() {
        return $this->start;
    }

    public function getSlutt() {
        return $this->slutt;
    }

    public function getVarighetMinutter() {
        return $this->varighetMinutter;
    }

    public function getMaksAntall() {
        return $this->maksAntall;
    }

    public function getAktivitet() : Aktivitet {
        return new Aktivitet($this->aktivitetId);
    }

    public function getHendelseId() {
        return $this->hendelseId;
    }

    public function getHendelse() : Hendelse {
        return new Hendelse($this->hendelseId);
    }

    public function getHarPaamelding() {
        return $this->harPaamelding;
    }

    public function getErSammeStedSomAktivitet() {
        return $this->erSammeStedSomAktivitet;
    }

    public function getErKunInterne() {
        return $this->kunInterne;
    }

    public function __toString() {
        $daysOfWeek = ['Søndag', 'Mandag', 'Tirsdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lørdag'];
        
        // Get day of week (0-6)
        $dayOfWeek = (int)$this->start->format('w');
        
        // Format times
        $startTime = $this->start->format('H:i');
        
        return $daysOfWeek[$dayOfWeek] . ' ' . $startTime;
    }

    /**
     * Hent alle deltakere for dette tidspunktet
     *
     */
    public function getDeltakere() {
        if($this->deltakere == null) {
            $this->deltakere = new SamlingDeltakere( $this->getId() );
        }
        return $this->deltakere;
    }

    public static function getLoadQry()
    {
        return "SELECT * FROM `aktivitet_tidspunkt` AS `aktivitet_tidspunkt`";
    }

    private function _load_by_id($id) {
        $qry = new Query(
            self::getLoadQry()
                . "WHERE `aktivitet_tidspunkt`.`tidspunkt_id` = '#id'",
            array('id' => $id)
        );

        $row = Query::fetch($qry->run());
        $this->_load_by_row($row);
    }

    private function _load_by_row($row)
    {
        if (!is_array($row)) {
            throw new Exception('AktivitetTidspunkt: _load_by_row krever dataarray!');
        }
        $this->tidspunktId = $row['tidspunkt_id'];
        $this->sted =  $row['sted'];
        $this->start = new DateTime($row['start']);
        $this->slutt = new DateTime($row['slutt']);
        $this->varighetMinutter = $row['varighet_min'];
        $this->maksAntall = $row['maksAntall'];
        $this->harPaamelding = $row['harPaamelding'] == 0 ? false : true;
        $this->erSammeStedSomAktivitet = $row['erSammeStedSomAktivitet'] == 0 ? false : true;
        $this->kunInterne = $row['kunInterne'];
        
        $this->aktivitetId = $row['aktivitet_id'];
        $this->hendelseId = $row['c_id'];
    }

    /**
     * Registrerer en deltaker i et tidspunkt. Det genereres en kode som skal brukes for å verifisere brukeren
     *
     * @param int $tidspunktId - tidspunkt hvor deltakeren skal meldes på
     * @param string $mobil - mobilnummer til deltakeren
     * @throws Exception
     * @return string - returnerer SMS koden som skal brukes for verifisering
     */
    public static function registrerDeltakerPaamelding(int $tidspunktId, string $mobil) : string {
        $tidspunkt = new AktivitetTidspunkt($tidspunktId);
        $deltakere = $tidspunkt->getDeltakere()->getKunVerifiserte();
        $aktivitet = $tidspunkt->getAktivitet();

        // Tidspuntet har ikke påmelding, alle kan delta og trengs ikke påmelding
        if($tidspunkt->getHarPaamelding() == false) {
            throw new Exception('Tidspunktet har ikke påmelding');
        }
        
        // Sjekk om brukeren er allerede meldt på
        foreach ($deltakere as $deltaker) {
            if ($deltaker->getMobil() === $mobil) {
                throw new Exception('Deltaker er allerede påmeldt');
            }
        }
        
        // Sjekk om det er plass til flere deltakere
        if ($tidspunkt->getMaksAntall() > 0 && count($deltakere) >= $tidspunkt->getMaksAntall()) {
            throw new Exception('Det er ikke plass til flere deltakere');
        }
        
        // Hvis det er kun interne, må mobil være med i arrangementet
        if($tidspunkt->getErKunInterne()) {
            $arrangement = $aktivitet->getArrangement();
            $brukerErIntern = false;
            foreach($arrangement->getInnslag()->getAll() as $innslag) {
                foreach($innslag->getPersoner()->getAll() as $person) {
                    if($person->getMobil() == $mobil) {
                        $brukerErIntern = true;
                        break 2; // Break out of both loops
                    }
                }
            }
            if(!$brukerErIntern) {
                throw new Exception('Brukeren er ikke intern i arrangementet og kan derfor ikke melde seg på');
            }
        }

        // Alt ok, registrer deltaker og vent for SMS verifikasjon senere
        $aktivitetDeltaker = null;
        try {
            $aktivitetDeltaker = Write::createAktivitetDeltaker($mobil);
        } catch(Exception $e) {
            throw new Exception('Kunne ikke registrere deltaker');
        }
        
        if($aktivitetDeltaker == null) {
            throw new Exception('Kunne ikke registrere deltaker');
        }

        // Brukeren er opprettet, connect brukeren til tidspunkt og generer SMS kode som skal brukes for verifikasjon senere        
        $generatedSMSCode = static::generateSmsCode(6, true);

        $res = false;
        try {
            $res = Write::addDeltakerToTidspunkt($aktivitetDeltaker->getId(), $tidspunktId, $generatedSMSCode);
        } catch(Exception $e) {
            throw new Exception('Kunne ikke registrere deltaker til tidspunkt');
        }

        if($res == false) {
            throw new Exception('Kunne ikke registrere deltaker til tidspunkt');
        }

        // Alt gikk bra, returner SMS kode
        return $generatedSMSCode;
    }

    /**
     * Verifiser deltakeren for et tidspunkt ved bruk av SMSkoden
     *
     * @param int $tidspunktId - tidspunkt hvor deltakeren skal verifiseres
     * @param string $mobil - mobilnummer til deltakeren
     * @param string $smsCode - koden som er lagret og skal brukes for verifisering
     * @throws Exception
     * @return bool - returnerer true hvis deltakeren er verifisert, false hvis ikke
     */
    public static function verifyDeltakerPaamelding(int $tidspunktId, string $mobil, string $smsCode) : bool {
        $tidspunkt = new AktivitetTidspunkt($tidspunktId);
        $res = false;
        try {
            $res = Write::verifyDeltaker($tidspunkt, $mobil, $smsCode);
        } catch(Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $res;
    }

    private static function generateSmsCode($length = 6, $includeText = true) {
        // Define possible characters for text and numbers
        $numbers = '0123456789';
        $text = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        
        // Combine text and numbers if $includeText is true
        $characters = $numbers;
        if ($includeText) {
            $characters .= $text;
        }
    
        // Generate a random SMS code
        $smsCode = '';
        $charactersLength = strlen($characters);
        for ($i = 0; $i < $length; $i++) {
            $smsCode .= $characters[rand(0, $charactersLength - 1)];
        }
    
        return $smsCode;
    }


    public function getArrObj() {
        $deltakere = [];

        foreach($this->getDeltakere()->getAll() as $deltaker) {
            if($deltaker->erAktiv()) {
                $deltakere[] = array(
                    'mobil' => $deltaker->getMobil(),
                    'aktiv' => $deltaker->erAktiv(),
                );
            }
        }

        $klokkeslett = AktivitetKlokkeslett::getByTidspunkt($this);

        return [
            'id' => $this->getId(),
            'start' => $this->getStart()->format('Y-m-d H:i:s'),
            'slutt' => $this->getSlutt()->format('Y-m-d H:i:s'),
            'sted' => $this->getSted(),
            'varighet' => $this->getVarighetMinutter(),
            'maksAntall' => $this->getMaksAntall(),
            'deltakere' => $deltakere,
            'hendelseId' => $this->getHendelseId(),
            'harPaamelding' => $this->getHarPaamelding(),
            'erSammeStedSomAktivitet' => $this->getErSammeStedSomAktivitet(),
            'erKunInterne' => $this->getErKunInterne(),
            'klokkeslett' => $klokkeslett != null ? $klokkeslett->getArrObj() : null,
        ];
    }

}