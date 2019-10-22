<?php

namespace UKMNorge\Innslag\Titler;

class Annet extends Tittel {
    public $real_type = 'annet';

    public function getParentes() {
        return $this->getFormat();
    }
}