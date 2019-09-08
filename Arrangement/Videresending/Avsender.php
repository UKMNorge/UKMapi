<?php

namespace UKMNorge\Arrangement\Videresending;
use Exception, DateTime;

require_once('UKM/Arrangement/Videresending/Videresender.php');

class Avsender extends Videresender {
    public function getPlId() {
        return $this->getTil();
    }
}