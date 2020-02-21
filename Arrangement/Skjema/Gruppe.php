<?php


namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Collection;

class Gruppe extends Collection
{

    public $overskrift;

    public function __construct(Int $id)
    {
        $this->id = $id;
    }

    /**
     * Hent overskriften
     *
     * @return String
     */
    public function getOverskrift()
    {
        return $this->overskrift;
    }

    /**
     * Opprett gruppe fra et overskrift-spørsmål
     *
     * @param Sporsmal $sporsmal
     * @return Gruppe
     */
    public static function createFromSporsmal(Sporsmal $sporsmal)
    {
        $gruppe = new static($sporsmal->getId());
        $gruppe->overskrift = $sporsmal->getTittel();

        return $gruppe;
    }

    /**
     * Opprett en gruppe fra ingenting
     *
     * @return Gruppe
     */
    public static function createEmpty()
    {
        $gruppe = new static(0);
        $gruppe->overskrift = 'Gruppe uten navn';
        return $gruppe;
    }
}
