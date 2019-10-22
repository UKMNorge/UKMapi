<?php

namespace UKMNorge\Arrangement\Videresending;
use Exception, DateTime;

require_once('UKM/Autoloader.php');

class Avsender extends Videresender {
    public function getPlId() {
        return $this->getFra();
    }

    public function getId() {
        return $this->getPlId();
    }
}