<?php

namespace UKMNorge\Innslag\Kommentarer;

use UKMNorge\Database\SQL\Query;

class Kommentar
{

    const TABLE = 'ukm_band_comment';

    private $id;
    private $innslag_id;
    private $arrangement_id;
    private $kommentar;

    public static function getLoadQuery()
    {
        "SELECT * FROM `" . static::TABLE . "`";
    }

    /**
     * Hent kommentar-objekt
     *
     * @param Int $innslag_id
     * @param Int $arrangement_id
     * @param String $kommentar
     * @param Int|null $id 
     * @return Kommentar
     */
    public function __construct(Int $innslag_id, Int $arrangement_id, String $kommentar, $id = null)
    {
        $this->innslag_id = $innslag_id;
        $this->arrangement_id = $arrangement_id;
        $this->kommentar = $kommentar;
        $this->id = $id;
    }

    /**
     * Er dette faktisk objekt eller placeholder?
     *
     * @return bool
     */
    public function eksisterer(): bool {
        return $this->id > 0;
    }

    /**
     * Er dette et placeholder-objekt?
     * 
     * Viktig for lagringen
     *
     * @return bool
     */
    public function erPlaceholder() {
        return !$this->eksisterer();
    }

    /**
     * Hent selve kommentaren
     *
     * @return string
     */
    public function getKommentar(): string
    {
        return $this->kommentar;
    }

    /**
     * Angi selve kommentaren
     *
     * @param string $kommentar
     * @return Kommentar
     */
    public function setKommentar(string $kommentar): Kommentar
    {
        $this->kommentar = $kommentar;
        return $this;
    }

    /**
     * Hent innslag-ID
     *
     * @return Int
     */
    public function getInnslagId(): Int
    {
        return $this->innslag_id;
    }

    /**
     * Hent arrangement-ID hvor kommentaren er registrert
     *
     * @return Int
     */
    public function getArrangementId(): Int
    {
        return $this->arrangement_id;
    }

    /**
     * Default toString er selve kommentaren
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getKommentar();
    }

    /**
     * Hent kommentar for innslag
     *
     * @param Int $innslag_id
     * @return Kommentar
     */
    public static function getByInnslagId(Int $innslag_id): Kommentar
    {
        $query = new Query(
            static::getLoadQuery() .
                " WHERE `innslag_id` = '#innslag_id'",
            [
                'innslag_id' => $innslag_id
            ]
        );

        $data = $query->getArray();

        return new static(
            (int) $innslag_id,
            (int) $data['arrangement_id'],
            $data['kommentar'],
            (int) $data['id']
        );
    }
}
