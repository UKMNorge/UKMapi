<?php

namespace UKMNorge\Arrangement\Kontaktperson;

interface KontaktInterface {

    public function getNavn();
    public function getFornavn();
    public function getEtternavn();
    public function getTelefon();
    public function getEpost();
    public function getFacebook();
}