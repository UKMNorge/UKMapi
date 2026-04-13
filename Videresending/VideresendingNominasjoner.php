<?php

namespace UKMNorge\Videresending;

use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class VideresendingNominasjoner extends Collection
{
    var $query;

    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function _load()
    {
        $res = $this->query->run();
        while ($row = Query::fetch($res)) {
            $this->add(new VideresendingNominasjon($row));
        }
    }

    public static function getAlleFraArrangement(int $arrangement_fra): self
    {
        $query = new Query(
            "SELECT * FROM `" . VideresendingNominasjon::TABLE . "` WHERE `arrangement_fra` = '#fra'" . VideresendingNominasjon::SQL_AND_KUN_AKTIVE,
            ['fra' => $arrangement_fra]
        );
        return new self($query);
    }

    public static function getAlleTilArrangement(int $arrangement_til): self
    {
        $query = new Query(
            "SELECT * FROM `" . VideresendingNominasjon::TABLE . "` WHERE `arrangement_til` = '#til'" . VideresendingNominasjon::SQL_AND_KUN_AKTIVE,
            ['til' => $arrangement_til]
        );
        return new self($query);
    }

    public static function getByInnslagId(int $innslag_id): self
    {
        $query = new Query(
            "SELECT * FROM `" . VideresendingNominasjon::TABLE . "` WHERE `b_id` = '#innslag_id'" . VideresendingNominasjon::SQL_AND_KUN_AKTIVE,
            ['innslag_id' => $innslag_id]
        );
        return new self($query);
    }

    public static function getAlleByPersonId(int $person_id, int $arrangement_id): VideresendingNominasjoner
    {
        $query = new Query(
            "SELECT * FROM `" . VideresendingNominasjon::TABLE . "` WHERE `p_id` = '#person_id' AND `arrangement_til` = '#arrangement_id'" . VideresendingNominasjon::SQL_AND_KUN_AKTIVE,
            ['person_id' => $person_id, 'arrangement_id' => $arrangement_id]
        );
        return new VideresendingNominasjoner($query);
    }

    public function getAll() {
        return parent::getAll();
    }
}
