<?php

namespace UKMNorge\Innslag\Mangler;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Innslag\Innslag;

class Mangler extends Collection
{

    var $kategoriSortert = [];

    public function __construct()
    { }

    /**
     * Hent alle mangler som JSON-string
     *
     * @return String $json
     */
    public function toJSON()
    {
        return json_encode($this->getAll());
    }


    /**
     * Legg til et nytt element
     *
     * @param Mangel $item
     * @return void
     */
    public function add($item)
    {
        if( !is_object($item) || get_class($item) != 'UKMNorge\Innslag\Mangler\Mangel') {
            throw new Exception(
                'Ugyldig testresultat. '. var_export($item,true)
            );
        }
        $this->kategoriSortert[$item->getKategori()][] = $item;
        parent::add($item);
    }

    /**
     * Er det noen mangler i gitt kategori?
     *
     * @param String $kategori
     * @return Bool har mangler
     */
    public function harKategori(String $kategori)
    {
        return isset($this->kategoriSortert[$kategori]);
    }

    /**
     * Hent alle mangler, sortert etter kategori
     *
     * @param String $kategori
     * @return Array<Mangel>
     * @throws Exception ingen mangler i gitt kategori
     * @see harKategori( $kategori )
     */
    public function getAllSortert(String $kategori = null)
    {
        if ($kategori !== null) {
            if ($this->harKategori($kategori)) {
                return $this->kategoriSortert[$kategori];
            }
            throw new Exception(
                'Har ikke mangler i  kategorien ' . $kategori
            );
        }
        return $this->kategoriSortert;
    }

    /**
     * Hent kategoriID for alle mangler
     *
     * @return Array<String> kategoriID
     */
    public function getKategorier()
    {
        $kategorier = [];
        foreach ($this->getAll() as $mangel) {
            $kategorier[] = $mangel->getKategori();
        }
        array_unique($kategorier);
        return $kategorier;
    }

    /**
     * Hent ut alle mangler fra JSON-string
     *
     * @param String $json
     * @return Mangler
     */
    public static function loadFromJSON(String $json)
    {
        $json_object = json_decode($json);

        $mangler = new Mangler();
        if (is_array($json_object)) {
            foreach ($json_object as $mangelData) {
                $mangler->add(Mangel::fraJSON($mangelData));
            }
        }
        return $mangler;
    }


    /**
     * Gå gjennom alle evalueringspunkter for et innslag
     *
     * @param Innslag $innslag
     * @return Mangler
     */
    public static function evaluer(Innslag $innslag)
    {
        $tests = [
            Mangel\Person::evaluer($innslag->getKontaktperson(), true),
            Mangel\Personer::evaluer($innslag),
            Mangel\Navn::evaluer($innslag),
            Mangel\Sjanger::evaluer($innslag),
            Mangel\Beskrivelse::evaluer($innslag),
            Mangel\TekniskeBehov::evaluer($innslag),
            Mangel\Titler::evaluer($innslag)
        ];

        $mangler = new Mangler();

        foreach ($tests as $testResults ) {
            if( is_array( $testResults ) && sizeof( $testResults ) > 0 ) {
                foreach( $testResults as $testResult ) {
                    $mangler->add( $testResult );
                }
            } elseif( !is_bool( $testResults ) ) {
                $mangler->add( $testResults );
            }
        }
        return $mangler;
    }

    public static function manglerOrTrue( Array $mangler) {
        if( sizeof( $mangler ) == 0 ) {
            return true;
        }
        return $mangler;
    }
}
