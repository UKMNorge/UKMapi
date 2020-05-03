<?php

namespace UKMNorge\Some\Forslag;

use DateTime;
use UKMNorge\Some\Kanaler\Kanal;
use UKMNorge\Some\Kanaler\Kanaler;

class Ide
{
    const TABLE = 'some_status_ide';
    const TABLE_REL_KANAL = 'some_status_ide_rel_kanaler';

    public $id;
    public $publisering;
    public $beskrivelse;
    public $kanaler;
    public $eier_id;
    public $team_id;


    public function __construct( Array $data )
    {
        $this->id = intval($data['faktisk_ide_id']);
        $this->publisering = new DateTime($data['publisering']);
        $this->beskrivelse = $data['beskrivelse'];
        $this->eier_id = $data['eier_id'];
        $this->team_id = $data['team_id'];
    }

    /**
     * Hent load-query for ideer
     *
     * @return String sql
     */
    public static function getLoadQuery()
    {
        return str_replace(
            [
                '#table_ide',
                '#table_rel',
                '#table_kanal'
            ],
            [
                static::TABLE,
                static::TABLE_REL_KANAL,
                Kanal::TABLE
            ],
            "SELECT *, `#table_ide`.`id` AS `faktisk_ide_id`
            FROM `#table_ide`
            LEFT JOIN `#table_rel`
                ON(`#table_ide`.`id` = `#table_rel`.`ide_id`)
            LEFT JOIN `#table_kanal` 
                ON(`#table_kanal`.`id` = `#table_rel`.`kanal_id`)"
        );
    }

    public function getId() {
        return $this->id;
    }

    public function getPubliseringsdato() {
        return $this->publisering;
    }

    public function setPubliseringsdato( DateTime $dato ) {
        $this->publisering = $dato;
        return $this;
    }

    public function getBeskrivelse() {
        return $this->beskrivelse;
    }

    public function setBeskrivelse( String $beskrivelse ) {
        $this->beskrivelse = $beskrivelse;
        return $this;
    }

    public function getKanaler() {
        if( is_null($this->kanaler) ) {
            $this->kanaler = new Kanaler('ide', $this->getId());
        }
        return $this->kanaler;
    }

    public function getEierId() {
        return $this->eier_id;
    }

    public function setEierId( String $eier_id ) {
        $this->eier_id = $eier_id;
        return $this;
    }

    public function getTeamId() {
        return $this->team_id;
    }

    public function setTeamId( String $team_id ) {
        $this->team_id = $team_id;
        return $this;
    }
}
