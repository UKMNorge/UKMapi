<?php

namespace UKMNorge\Innslag\Titler;

class Dans extends Tittel {
    public $real_type = 'dans';
    
    public function getParentes() {
        return 'Koreografi: '. $this->getKoreografiAv();
    }
}