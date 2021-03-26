<?php

namespace UKMNorge\Innslag\Personer;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Innslag\Context\Context;
use UKMNorge\Innslag\Samling;
use UKMNorge\Innslag\Typer\Type;

class Kontaktperson extends Person
{

    private $innslag;
    private $type_innslag_map = [];

    /**
     * Har kontaktpersonen et innslag for denne typen innslag?
     *
     * @param Type $type
     * @param Arrangement $arrangement
     * @param boolean $inkluder_ufullstendige
     * @return boolean
     */
    public function harInnslagFor(Type $type, Arrangement $arrangement, bool $inkluder_ufullstendige = false)
    {
        try {
            $this->getInnslagFor($type, $arrangement, $inkluder_ufullstendige);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Hent innslag som denne personen er kontaktperson for
     *
     * @param Type $type
     * @param Arrangement $arrangement
     * @param boolean $inkluder_ufullstendige
     * @return Samling
     */
    public function getInnslagFor(Type $type, Arrangement $arrangement, bool $inkluder_ufullstendige = false)
    {
        $id = $type->getKey().'-'.$arrangement->getId();
        
        // Hent fra cache
        if (isset($this->type_innslag_map[$id])) {
            return $this->type_innslag_map[$id];
        }

        // Let gjennom påmeldte innslag
        foreach ($this->getInnslag()->getAll() as $innslag) {
            if ($innslag->getType()->getKey() == $type->getKey()) {
                $this->type_innslag_map[$innslag->getType()->getKey().'-'.$innslag->getHomeId()] = $innslag;
            }
        }

        // Let gjennom ikke påmeldte innslag
        if ($inkluder_ufullstendige) {
            foreach ($this->getInnslag()->getAllUfullstendige() as $innslag) {
                if ($innslag->getType()->getKey() == $type->getKey()) {
                    $this->type_innslag_map[$innslag->getType()->getKey().'-'.$innslag->getHomeId()] = $innslag;
                }
            }
        }

        // Vi har ett (og systemet skal unngå at det finnes flere)!
        if (isset($this->type_innslag_map[$id])) {
            return $this->type_innslag_map[$id];
        }

        // Det finnes ingen
        throw new Exception(
            $this->getNavn() . ' har ingen ' . $type->getNavn() . '-innslag',
            124001
        );
    }

    /**
     * Hent alle innslag personen er kontaktperson for
     *
     * @return Samling
     */
    public function getInnslag()
    {
        if ($this->innslag == null) {
            $this->loadInnslag();
        }
        return $this->innslag;
    }

    /**
     * Last inn alle innslag
     * 
     * Funksjonen brukes i det tilfelle personen er kontaktperson.
     * 
     * @return Samling
     */
    private function loadInnslag()
    {
        $context = Context::createKontaktperson($this->getId());
        $this->innslag = new Samling($context);

        try {
            foreach ($this->innslag->getAll() as $innslag) {
                try {
                    $innslag->getHome();
                    /*
                    if( $innslag->getHome()->erFerdig() ) {
                        $this->innslag->fjern($innslag);
                    }
                    */
                } catch (Exception $e) {
                    // Workaround for noen få brukere som har slettede innslag.
                    $this->innslag->fjern($innslag);
                }
            }
        } catch (Exception $e) {
            // Ukjent innslag-type (vi har opplevd dette på en del innslag fra tidligere år)
            if ($e->getCode() != 110002) {
                throw $e;
            }
        }
        return $this->innslag;
    }
}
