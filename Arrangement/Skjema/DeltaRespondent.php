<?php
namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Database\SQL\Query;

use Exception;

class DeltaRespondent
{
    public $id;
    public $navn;
    public $etternavn;
    public $mobil;
    public $videresending_nominasjon;
    /** Fylkesnavn fra avsender-arrangement (videresending). */
    public ?string $fylke = null;
    /** Navn på avsender-arrangement (videresending). */
    public ?string $arrangement = null;
    /** Foresatts navn fra ukm_user (Delta). */
    public ?string $foresatt_navn = null;
    /** Foresatts mobilnummer fra ukm_user (Delta). */
    public ?string $foresatt_mobil = null;

    public bool $is_real = true;

    public function __construct($id, $navn, $etternavn, $mobil)
    {
        $this->id = $id;
        $this->navn = $navn;
        $this->etternavn = $etternavn;
        $this->mobil = $mobil;
        $this->videresending_nominasjon = false;
    }


    public static function loadByMobil($mobil) : DeltaRespondent|null{
        try {
            $query = new Query(
                "SELECT id, first_name AS navn, last_name AS etternavn, phone AS mobil, foresatt_navn, foresatt_mobil FROM ukm_user WHERE phone = '#mobil'",
                ['mobil' => $mobil],
                'ukmdelta'
            );
            $res = $query->run('array');
            if(!$res) {
                return null;
            }
            return self::fromDeltaRow($res);
        } catch(Exception $e) {
            return null;
        }
    }

    public static function getWithoutExisting(?string $fornavn, ?string $etternavn, string $mobil) : DeltaRespondent {
        $id = (int)$mobil < 1 ? static::generateRandomId() : $mobil.'5555777';
        $respondent = new DeltaRespondent($id, $fornavn, $etternavn, $mobil);
        $respondent->is_real = false;
        return $respondent;
    }

    private static function generateRandomId() : int {
        return rand(10000000000000, 999999999999999);
    }

    public static function loadById($id) : DeltaRespondent|null{
        try {
            $query = new Query(
                "SELECT id, first_name AS navn, last_name AS etternavn, phone AS mobil, foresatt_navn, foresatt_mobil FROM ukm_user WHERE id = '#id'",
                ['id' => $id],
                'ukmdelta'
            );
            $res = $query->run('array');
            if(!$res) {
                return null;
            }
            return self::fromDeltaRow($res);
        }
        catch(Exception $e) {
            return null;
        }
    }

    private static function fromDeltaRow(array $row): DeltaRespondent
    {
        $respondent = new DeltaRespondent($row['id'], $row['navn'], $row['etternavn'], $row['mobil']);
        $foresattNavn = isset($row['foresatt_navn']) ? trim((string) $row['foresatt_navn']) : '';
        $respondent->foresatt_navn = $foresattNavn !== '' ? $foresattNavn : null;
        $respondent->foresatt_mobil = self::formatForesattMobil($row['foresatt_mobil'] ?? null);
        return $respondent;
    }

    private static function formatForesattMobil($raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', (string) $raw);
        return $digits !== '' ? $digits : null;
    }

    public function isReal() : bool
    {
        return $this->is_real;
    }


    public function getId()
    {
        return $this->id;
    }

    public function getNavn()
    {
        return $this->navn;
    }

    public function getEtternavn()
    {
        return $this->etternavn;
    }

    public function getMobil()
    {
        return $this->mobil;
    }

    public function getForesattNavn(): ?string
    {
        return $this->foresatt_navn;
    }

    public function getForesattMobil(): ?string
    {
        return $this->foresatt_mobil;
    }

    public function getNavnFullt()
    {
        return $this->navn . ' ' . $this->etternavn;
    }


}