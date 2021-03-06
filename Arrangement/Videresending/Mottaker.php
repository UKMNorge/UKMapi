<?php

namespace UKMNorge\Arrangement\Videresending;
use Exception, DateTime;

require_once('UKM/Autoloader.php');

class Mottaker extends Videresender {
    public function getPlId() {
        return $this->getTil();
    }
    public function getId() {
        return $this->getPlId();
    }

    /**
     * Opprett en mottaker
     *
     * @param Int $til
     * @param Int $fra
     */
    public function __construct(Int $til, Int $fra ) {
        parent::__construct( $fra, $til );
    }
}