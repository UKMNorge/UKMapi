<?php

namespace UKMNorge\OAuth2;

use Exception;

// Midlertidig bruker eksisterer ikke i database. 
// Det er ment til å være en bruker som eksisterer bare i minne. 
// Kan brukes for å opprere nye brukere.
class TempUser extends User {

    public function __construct(string $tel_nr, string $first_name, string $last_name, $birthday) {
        parent::setTelNumber($tel_nr);
        parent::setTelCountryCode(); // +47 default
        parent::setFirstName($first_name);
        parent::setLastName($last_name);
        parent::setBirthday($birthday);
    }


    // Override
    public function update() {
        throw new Exception('En midlertidig bruker kan ikke opdateres');
    }

    // Override
    public function save() {
        throw new Exception('En midlertidig bruker kan ikke lagres i database');
    }

}