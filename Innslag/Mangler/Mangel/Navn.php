<?php

namespace UKMNorge\Innslag\Mangler\Mangel;

use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Mangler\Mangel;

class Navn
{
    public static function evaluer(Innslag $innslag)
    {
        if (empty($innslag->getNavn()) || $innslag->getNavn() == 'Innslag uten navn') {
            return new Mangel(
                'innslag.navn',
                'Innslaget har ikke navn',
                'Innslaget må ha et navn',
                'innslag',
                $innslag->getId()
            );
        }
        return true;
    }
}
