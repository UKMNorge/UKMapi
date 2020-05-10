<?php

namespace UKMNorge\Some\Forslag;

use DateTime;
use UKMNorge\Slack\Cache\User\User;
use UKMNorge\Slack\Cache\User\Users;
use UKMNorge\Some\Kanaler\Kanal;
use UKMNorge\Some\Kanaler\Kanaler;

class Ide
{
    const TABLE = 'some_status_ide';
    const TABLE_REL_KANAL = 'some_status_ide_rel_kanaler';

    public $id;
    public $publisering;
    public $hva;
    public $beskrivelse;
    public $kanaler;
    public $eier_id;
    public $team_id;


    public function __construct( Array $data )
    {
        $this->id = intval($data['faktisk_ide_id']);
        $this->publisering = new DateTime($data['publisering']);
        $this->hva = $data['hva'];
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

    /**
     * Hent hva som skal deles
     *
     * @return String
     */
    public function getHva() {
        return $this->hva;
    }

    /**
     * Set hva som skal deles
     *
     * @param String $hva
     * @return self
     */
    public function setHva( String $hva ) {
        $this->hva = $hva;
        return $this;
    }

    /**
     * Hent forslagets id
     *
     * @return Int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Hent forslagets ønskede publiseringsdato
     *
     * @return DateTime
     */
    public function getPubliseringsdato() {
        return $this->publisering;
    }

    /**
     * Set ønsket publiseringsdato
     *
     * @param DateTime $dato
     * @return self
     */
    public function setPubliseringsdato( DateTime $dato ) {
        $this->publisering = $dato;
        return $this;
    }

    /**
     * Hent beskrivelse / tekst for det som skal deles
     *
     * @return String
     */
    public function getBeskrivelse() {
        return $this->beskrivelse;
    }

    /**
     * Set beskrivelse / tekst for det som skal deles
     *
     * @param String $beskrivelse
     * @return self
     */
    public function setBeskrivelse( String $beskrivelse ) {
        $this->beskrivelse = $beskrivelse;
        return $this;
    }

    /**
     * Hent en samling med ønskede kanaler
     *
     * @return Kanaler
     */
    public function getKanaler() {
        if( is_null($this->kanaler) ) {
            $this->kanaler = new Kanaler('ide', $this->getId());
        }
        return $this->kanaler;
    }

    /**
     * Hent eierens ID
     *
     * @return String
     */
    public function getEierId() {
        return $this->eier_id;
    }

    /**
     * Angi eierens id
     *
     * @param String $eier_id
     * @return self
     */
    public function setEierId( String $eier_id ) {
        $this->eier_id = $eier_id;
        return $this;
    }

    /**
     * Hent eier-objektet
     *
     * @return User
     */
    public function getEier() {
        if( is_null($this->eier)) {
            $this->eier = Users::getBySlackId( $this->getEierId());
        }
        return $this->eier;
    }

    /**
     * Hent deeplink for eieren
     *
     * @return String html
     */
    public function getEierLink() {
        return '<a href="slack://user?team='.
            $this->getTeamId() . '&id='. $this->getEierId() .'">'.
            $this->getEier()->getRealName() .
            '</a>';
    }

    /**
     * Hent eierens team-id 
     *
     * @return String
     */
    public function getTeamId() {
        return $this->team_id;
    }

    /**
     * Angi eierens team-id
     *
     * @param String $team_id
     * @return self
     */
    public function setTeamId( String $team_id ) {
        $this->team_id = $team_id;
        return $this;
    }
}
