<?php

namespace UKMNorge\Innslag\Titler;

class Film extends Tittel {
    public $real_type = 'film';

    public function getParentes() {
        return ''; // Tidligere $this->getFormat()
    }
}