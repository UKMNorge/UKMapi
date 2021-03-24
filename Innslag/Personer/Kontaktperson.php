<?php

namespace UKMNorge\Innslag\Personer;

use Exception;
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
     * @param boolean $inkluder_ufullstendige
     * @return boolean
     */
    public function harInnslagFor(Type $type, bool $inkluder_ufullstendige = false)
    {
        try {
            $this->getInnslagFor($type, $inkluder_ufullstendige);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Hent innslag som denne personen er kontaktperson for
     *
     * @param Type $type
     * @param boolean $inkluder_ufullstendige
     * @return Samling
     */
    public function getInnslagFor(Type $type, bool $inkluder_ufullstendige = false)
    {
        // Hent fra cache
        if (isset($this->type_innslag_map[$type->getKey()])) {
            return $this->type_innslag_map[$type->getKey()];
        }

        // Let gjennom påmeldte innslag
        foreach ($this->getInnslag()->getAll() as $innslag) {
            if ($innslag->getType()->getKey() == $type->getKey()) {
                $this->type_innslag_map[$innslag->getType()->getKey()] = $innslag;
            }
        }

        // Let gjennom ikke påmeldte innslag
        if ($inkluder_ufullstendige) {
            foreach ($this->getInnslag()->getAllUfullstendige() as $innslag) {
                if ($innslag->getType()->getKey() == $type->getKey()) {
                    $this->type_innslag_map[$innslag->getType()->getKey()] = $innslag;
                }
            }
        }

        // Det finnes ingen
        if (!isset($this->type_innslag_map[$type->getKey()])) {
            throw new Exception(
                $this->getNavn() . ' har ingen ' . $type->getNavn() . '-innslag',
                124001
            );
        }

        // Vi har ett (og systemet skal unngå at det finnes flere)!
        return $this->type_innslag_map[$type->getKey()];
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
