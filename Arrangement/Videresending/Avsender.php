<?php

namespace UKMNorge\Arrangement\Videresending;

use Exception, DateTime;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Innslag\Context\Context;
use UKMNorge\Innslag\Samling;

require_once('UKM/Autoloader.php');

class Avsender extends Videresender
{
    public function getPlId()
    {
        return $this->getFra();
    }

    public function getId()
    {
        return $this->getPlId();
    }

    /**
     * Hent arrangement-objekt som skal motta videresending
     *
     * @return Arrangement
     */
    public function getArrangementMottaker()
    {
        if (is_null($this->arrangement_til)) {
            $this->arrangement_til = new Arrangement($this->getTil());
        }
        return $this->arrangement_til;
    }

    /**
     * Hent antall videresendte til gitt arrangement
     *
     * @return Int
     */
    public function getAntallVideresendte()
    {
        return $this->getVideresendte()->getAntall();
    }

    /**
     * Hent en samling videresendte innslag
     *
     * @return Samling
     */
    public function getVideresendte()
    {
        if (is_null($this->innslag)) {
            $this->innslag = new Samling(
                Context::createVideresending(
                    $this->getArrangement(),
                    $this->getArrangementMottaker()
                )
            );
        }

        return $this->innslag;
    }
}
