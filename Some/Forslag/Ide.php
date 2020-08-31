<?php

namespace UKMNorge\Some\Forslag;

use DateTime;
use UKMNorge\Slack\Cache\User\User;
use UKMNorge\Slack\Cache\User\Users;
use UKMNorge\Some\Kanaler\Kanal;
use UKMNorge\Some\Kanaler\Kanaler;
use UKMNorge\Some\Log\Log;
use UKMNorge\Some\Tekst\Tekster;

class Ide
{
    const TABLE = 'some_status_ide';
    const TABLE_REL_KANAL = 'some_status_ide_rel_kanaler';

    public $id;
    public $publisering;
    public $hva;
    public $beskrivelse;
    public $kanaler;
    public $eier;
    public $log;
    public $tekster;
    public $ansvarlig;

    public function __construct(array $data)
    {
        $this->id = intval($data['faktisk_ide_id']);
        $this->publisering = new DateTime($data['publisering']);
        $this->hva = $data['hva'];
        $this->beskrivelse = $data['beskrivelse'];
        $this->eier = Users::getProxy($data['eier_team_id'], $data['eier_id']);
        if (!is_null($data['ansvarlig_team_id']) && !is_null($data['ansvarlig_id'])) {
            $this->ansvarlig = Users::getProxy($data['ansvarlig_team_id'], $data['ansvarlig_id']);
        }
    }

    /**
     * Konverter objektet til array
     *
     * @return Array
     */
    public function __toArray()
    {
        return [
            'id' => $this->getId(),
            'publiseringsdato' => $this->getPubliseringsdato()->format(DateTime::RFC3339),
            'hva' => $this->getHva(),
            'beskrivelse' => $this->getBeskrivelse(),
            'kanaler' => $this->getKanaler()->__toArray(),
            'eier' => (array) $this->getEier(),
            'ansvarlig' => (array) $this->getAnsvarlig(),
            'tekster' => $this->getTekster()->__toArray()
        ];
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
     * Hent lenke til public preview av saken
     *
     * @return String
     */
    public function getLink()
    {
        return 'https://ukm.no/wp-admin/user/admin.php?page=UKMmarketing&action=some&forslag=' . $this->getId();
    }

    /**
     * Hent logg for hva som har skjedd med ideen
     *
     * @return Log
     */
    public function getLog()
    {
        if (is_null($this->log)) {
            $this->log = new Log(static::class, $this->getId());
        }
        return $this->log;
    }

    /**
     * Hent hva som skal deles
     *
     * @return String
     */
    public function getHva()
    {
        return $this->hva;
    }

    /**
     * Set hva som skal deles
     *
     * @param String $hva
     * @return self
     */
    public function setHva(String $hva)
    {
        $this->hva = $hva;
        return $this;
    }

    /**
     * Hent forslagets id
     *
     * @return Int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent forslagets ønskede publiseringsdato
     *
     * @return DateTime
     */
    public function getPubliseringsdato()
    {
        return $this->publisering;
    }

    /**
     * Set ønsket publiseringsdato
     *
     * @param DateTime $dato
     * @return self
     */
    public function setPubliseringsdato(DateTime $dato)
    {
        $this->publisering = $dato;
        return $this;
    }

    /**
     * Hent beskrivelse / tekst for det som skal deles
     *
     * @return String
     */
    public function getBeskrivelse()
    {
        return $this->beskrivelse;
    }

    /**
     * Set beskrivelse / tekst for det som skal deles
     *
     * @param String $beskrivelse
     * @return self
     */
    public function setBeskrivelse(String $beskrivelse)
    {
        $this->beskrivelse = $beskrivelse;
        return $this;
    }

    /**
     * Hent en samling med ønskede kanaler
     *
     * @return Kanaler
     */
    public function getKanaler()
    {
        if (is_null($this->kanaler)) {
            $this->kanaler = new Kanaler('ide', $this->getId());
        }
        return $this->kanaler;
    }

    /**
     * Hent en samling av tilknyttede tekster
     *
     * @return Tekster
     */
    public function getTekster()
    {
        if (is_null($this->tekster)) {
            $this->tekster = new Tekster('ide', $this->getId());
        }
        return $this->tekster;
    }

    /**
     * Hent eier
     *
     * @return User
     */
    public function getEier()
    {
        return $this->eier;
    }

    /**
     * Oppdater eier-objekt
     *
     * @param User $eier
     * @return self
     */
    public function setEier(User $eier)
    {
        $this->eier = $eier;
        return $this;
    }

    /**
     * Hent ansvarlig for oppfølgingen
     *
     * @return User
     */
    public function getAnsvarlig()
    {
        return $this->ansvarlig;
    }

    /**
     * Angi ansvarlig for oppfølgingen
     *
     * @param User $ansvarlig
     * @return self
     */
    public function setAnsvarlig(User $ansvarlig)
    {
        $this->ansvarlig = $ansvarlig;
        return $this;
    }
}
