<?php

namespace UKMNorge\UKMWebinar;

use Exception;
use DateTime;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Innslag\Context\Context;
use UKMNorge\Innslag\Typer\Typer;
use UKMNorge\Sensitivt\Person as PersonSensitivt;
use UKMNorge\Wordpress\User;
use UKMNorge\Tools\Sanitizer;

require_once('UKM/Autoloader.php');

class TeamsUKMWebinar {
    public string $id;
    public string $audience;
    public string $status;
    public string $navn;
    public string $beskrivelse;
    public DateTime $dato_start;
    public DateTime $dato_slutt;

    public function __construct($id, $audience, $status, $name, $description, DateTime $startDate, DateTime $endDate) {
        $this->id = $id;
        $this->audience = $audience;
        $this->status = $status;
        $this->navn = $name;
        $this->beskrivelse = $description;
        $this->dato_start = $startDate;
        $this->dato_slutt = $endDate;
    }

    public function getId() {
        return $this->id;
    }

    public function getAudience() {
        return $this->audience;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getNavn() {
        return $this->navn;
    }

    public function getBeskrivelse() {
        return $this->beskrivelse;
    }

    public function getStart() : DateTime {
        return $this->dato_start;
    }

    public function getSlutt() : DateTime {
        return $this->dato_slutt;
    }

    public function isActive() : bool {
        return 
        (clone $this->dato_slutt)->modify('+2 days') > new DateTime() &&
            $this->status != 'canceled' &&
            $this->status != 'draft';
    }

    public function getURL() : string {
        return "https://events.teams.microsoft.com/event/{$this->id}";
    }

}