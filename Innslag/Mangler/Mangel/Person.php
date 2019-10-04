<?php

namespace UKMNorge\Innslag\Mangler\Mangel;

use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Mangler\Mangel;
use UKMNorge\Innslag\Personer\Person as InnslagPerson;

class Person
{
    public static function evaluer(InnslagPerson $person, Bool $kontaktperson = false)
    {
        $mangler = [];

        if (empty($person->getFornavn())) {
            $mangler[] = new Mangel(
                'person.fornavn',
                'Fornavn mangler',
                empty($person->getNavn()) ? 'Deltakeren har ikke navn' : $person->getEtternavn() . ' har ikke fornavn',
                'person',
                $person->getId()
            );
        }

        if (empty($person->getEtternavn())) {
            $mangler[] = new Mangel(
                'person.etternavn',
                'Etternavn mangler',
                empty($person->getNavn()) ? 'Deltakeren har ikke navn' : $person->getFornavn() . ' har ikke etternavn',
                'person',
                $person->getId()
            );
        }

        if (empty($person->getMobil())) {
            $mangler[] = new Mangel(
                'person.mobil',
                'Mobilnummer mangler',
                empty($person->getNavn()) ? 'Deltakeren' : $person->getNavn() . ' har ikke oppgitt mobilnummer',
                'person',
                $person->getId()
            );
        }

        // HVIS VANLIG DELTAKER (IKKE KONTAKTPERSON)
        if (!$kontaktperson) {
            if (empty($person->getRolle())) {
                $mangler[] = new Mangel(
                    'person.rolle',
                    'Rolle/instrument mangler',
                    'Det er ikke oppgitt hvilken rolle/instrument ' . empty($person->getNavn()) ? 'deltakeren' : $person->getNavn() . ' har.',
                    'person',
                    $person->getId()
                );
            }
        }
        // HVIS KONTAKTPERSON
        else {
            if (!static::testMobil($person->getMobil())) {
                $mangler[] = new Mangel(
                    'kontaktperson.mobil',
                    'Ugyldig mobilnummer',
                    'Mobilnummer må bestå av 8 siffer og være et gyldig mobilnummer',
                    'person',
                    $person->getId()
                );
            }
            if (!static::testEpost($person->getEpost())) {
                $mangler[] = new Mangel(
                    'kontaktperson.epost',
                    'Ugyldig e-postadresse',
                    'E-postadressen til kontaktpersonen må være en gyldig e-postadresse',
                    'person',
                    $person->getId()
                );
            }
        }

        return $mangler;
    }

    public static function testMobil($mobilnummer)
    {

        $mobilnummer = (int) $mobilnummer;

        if (strlen((string) $mobilnummer) != 8) {
            return false;
        }

        if ($mobilnummer < 40000000) {
            return false;
        }
        if ($mobilnummer > 49999999 && $mobilnummer < 90000000) {
            return false;
        }

        $ugyldige_mobilnummer = [
            '98765432',
            '99999999'
        ];

        if (in_array($mobilnummer, $ugyldige_mobilnummer)) {
            return false;
        }

        return true;
    }

    public static function testEpost($epostadresse)
    {
        $isValid = true;
        $atIndex = strrpos($epostadresse, "@");
        if (is_bool($atIndex) && !$atIndex) {
            $isValid = false;
        } else {
            $domain = substr($epostadresse, $atIndex + 1);
            $local = substr($epostadresse, 0, $atIndex);
            $localLen = strlen($local);
            $domainLen = strlen($domain);
            if ($localLen < 1 || $localLen > 64) {
                // local part length exceeded
                $isValid = false;
            } else if ($domainLen < 1 || $domainLen > 255) {
                // domain part length exceeded
                $isValid = false;
            } else if ($local[0] == '.' || $local[$localLen - 1] == '.') {
                // local part starts or ends with '.'
                $isValid = false;
            } else if (preg_match('/\\.\\./', $local)) {
                // local part has two consecutive dots
                $isValid = false;
            } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                // character not valid in domain part
                $isValid = false;
            } else if (preg_match('/\\.\\./', $domain)) {
                // domain part has two consecutive dots
                $isValid = false;
            } else if (!preg_match(
                '/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/',
                str_replace("\\\\", "", $local)
            )) {
                // character not valid in local part unless 
                // local part is quoted
                if (!preg_match(
                    '/^"(\\\\"|[^"])+"$/',
                    str_replace("\\\\", "", $local)
                )) {
                    $isValid = false;
                }
            }
            if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
                // domain not found in DNS
                $isValid = false;
            }
        }
        return $isValid;
    }
}
