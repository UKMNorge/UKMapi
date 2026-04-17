<?php

namespace UKMNorge\Videresending;

use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Arrangement\Arrangement;

require_once('UKM/Autoloader.php');

class VideresendingNominasjon
{
    const TABLE = 'videresending_nominasjon';

    /** Brukes i alle listespørringer: kun synlige rader (active = 1). */
    public const SQL_AND_KUN_AKTIVE = ' AND `active` = 1';

    public const STATUS_HOS_AVSENDER = 'hos-avsender';
    public const STATUS_HOS_MOTTAKER = 'hos-mottaker';
    public const STATUS_HOS_DELTAKER = 'hos-deltaker';
    public const STATUS_GODKJENT = 'godkjent';

    protected int $id;
    protected ?int $p_id; // Participant ID
    protected ?int $b_id; // Innslag ID
    protected ?int $t_id; // Tittel ID
    protected int $season;
    protected string $innslag_type;
    protected int $arrangement_fra; // Arrangement ID
    protected int $arrangement_til; // Arrangement ID
    protected bool $godkjent;
    protected ?string $beskrivelse;
    protected ?string $status;
    protected bool $active;

    /**
     * @param int|array $data ID eller rad fra databasen
     */
    public function __construct($data)
    {
        if (is_numeric($data)) {
            $this->_loadById((int) $data);
        } elseif (is_array($data)) {
            $this->_loadByRow($data);
        } else {
            throw new Exception('VideresendingNominasjon krever numerisk ID eller database-rad.');
        }
    }

    public static function getAlleTilArrangement(int $arrangement_id): VideresendingNominasjoner
    {
        return VideresendingNominasjoner::getAlleTilArrangement($arrangement_id);
    }

    public static function getAlleByInnslagId(int $innslag_id): VideresendingNominasjoner
    {
        return VideresendingNominasjoner::getByInnslagId($innslag_id);
    }

    public static function getAlleByTittelId(int $tittel_id, int $arrangement_id): VideresendingNominasjoner
    {
        $query = new Query(
            "SELECT * FROM `" . self::TABLE . "` WHERE `t_id` = '#tittel_id' AND `arrangement_til` = '#arrangement_id'" . self::SQL_AND_KUN_AKTIVE,
            ['tittel_id' => $tittel_id, 'arrangement_id' => $arrangement_id]
        );
        return new VideresendingNominasjoner($query);
    }

    /**
     * Finn rad ut fra unik nøkkel (fra/til-innslag, person, tittel). Brukes for duplikatkontroll og reaktivering.
     *
     * @param bool $kunAktive true: bare active = 1; false: også deaktivert (for reaktivering ved ny videresending).
     */
    public static function finnVedNokkel(
        int $arrangement_fra,
        int $arrangement_til,
        ?int $b_id,
        ?int $p_id,
        ?int $t_id,
        bool $kunAktive = true
    ): ?self {
        $where = [
            "`arrangement_fra` = '#arrangement_fra'",
            "`arrangement_til` = '#arrangement_til'",
        ];
        $params = [
            'arrangement_fra' => $arrangement_fra,
            'arrangement_til' => $arrangement_til,
        ];
        if ($b_id === null) {
            $where[] = '`b_id` IS NULL';
        } else {
            $where[] = "`b_id` = '#b_id'";
            $params['b_id'] = $b_id;
        }
        if ($p_id === null) {
            $where[] = '`p_id` IS NULL';
        } else {
            $where[] = "`p_id` = '#p_id'";
            $params['p_id'] = $p_id;
        }
        if ($t_id === null) {
            $where[] = '`t_id` IS NULL';
        } else {
            $where[] = "`t_id` = '#t_id'";
            $params['t_id'] = $t_id;
        }
        if ($kunAktive) {
            $where[] = '`active` = 1';
        }
        $sql = 'SELECT * FROM `' . self::TABLE . '` WHERE ' . implode(' AND ', $where) . ' LIMIT 1';
        $query = new Query($sql, $params);
        $res = $query->run('array');
        if (!$res) {
            return null;
        }
        return new self($res);
    }

    public static function getAlleFraArrangement(int $arrangement_id): VideresendingNominasjoner
    {
        return VideresendingNominasjoner::getAlleFraArrangement($arrangement_id);
    }

    protected function _loadById(int $id): void
    {
        $sql = new Query(
            "SELECT * FROM `" . self::TABLE . "` WHERE `id` = '#id'",
            ['id' => $id]
        );
        $res = $sql->run('array');
        if ($res) {
            $this->_loadByRow($res);
            return;
        }
        throw new Exception('VideresendingNominasjon med ID ' . $id . ' finnes ikke.');
    }

    protected function _loadByRow(array $row): void
    {
        $this->id = (int) $row['id'];
        $this->p_id = isset($row['p_id']) && $row['p_id'] !== null && $row['p_id'] !== '' ? (int) $row['p_id'] : null;
        $this->b_id = isset($row['b_id']) && $row['b_id'] !== null && $row['b_id'] !== '' ? (int) $row['b_id'] : null;
        $this->t_id = isset($row['t_id']) && $row['t_id'] !== null && $row['t_id'] !== '' ? (int) $row['t_id'] : null;
        $this->season = (int) $row['season'];
        $this->innslag_type = (string) $row['innslag_type'];
        $this->arrangement_fra = (int) $row['arrangement_fra'];
        $this->arrangement_til = (int) $row['arrangement_til'];
        $this->godkjent = (bool) (int) $row['godkjent'];
        $this->beskrivelse = isset($row['beskrivelse']) && $row['beskrivelse'] !== null
            ? (string) $row['beskrivelse']
            : null;
       
        $statusRaw = $row['status'];
        self::krevGyldigStatus($statusRaw);
        $this->status = $statusRaw;
        $this->active = isset($row['active']) ? (bool) (int) $row['active'] : true;
    }


    public static function getGyldigeStatuser(): array
    {
        return [
            self::STATUS_HOS_AVSENDER,
            self::STATUS_HOS_MOTTAKER,
            self::STATUS_HOS_DELTAKER,
            self::STATUS_GODKJENT,
        ];
    }

    public static function krevGyldigStatus(?string $status): void
    {
        if($status === null) {
            return;
        }
        static $gyldige = [
            self::STATUS_HOS_AVSENDER,
            self::STATUS_HOS_MOTTAKER,
            self::STATUS_HOS_DELTAKER,
            self::STATUS_GODKJENT,
        ];
        if (!in_array($status, $gyldige, true)) {
            throw new Exception('Ugyldig nominasjonsstatus: ' . $status);
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPId(): ?int
    {
        return $this->p_id;
    }

    public function getBId(): ?int
    {
        return $this->b_id;
    }

    public function getTId(): ?int
    {
        return $this->t_id;
    }

    public function setTId(?int $t_id): void
    {
        $this->t_id = $t_id;
    }

    public function getSeason(): int
    {
        return $this->season;
    }

    public function getInnslagType(): string
    {
        return $this->innslag_type;
    }

    public function getArrangementFraId(): int
    {
        return $this->arrangement_fra;
    }

    public function getArrangementTilId(): int
    {
        return $this->arrangement_til;
    }

    public function getArrangementFra(): Arrangement
    {
        return new Arrangement($this->arrangement_fra);
    }

    public function getArrangementTil(): Arrangement
    {
        return new Arrangement($this->arrangement_til);
    }

    public function getGodkjent(): bool
    {
        return $this->godkjent;
    }

    public function getBeskrivelse(): string
    {
        return $this->beskrivelse ?? '';
    }

    public function getStatus(): string
    {
        if($this->status === null) {
            return 'Ukjent';
        }
        return $this->status;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function setPId(?int $p_id): void
    {
        $this->p_id = $p_id;
    }

    public function setBId(?int $b_id): void
    {
        $this->b_id = $b_id;
    }

    public function setSeason(int $season): void
    {
        $this->season = $season;
    }

    public function setInnslagType(string $innslag_type): void
    {
        $this->innslag_type = $innslag_type;
    }

    public function setArrangementFra(int $arrangement_fra): void
    {
        $this->arrangement_fra = $arrangement_fra;
    }

    public function setArrangementTil(int $arrangement_til): void
    {
        $this->arrangement_til = $arrangement_til;
    }

    public function setGodkjent(bool $godkjent): void
    {
        $this->godkjent = $godkjent;
    }

    public function setBeskrivelse(?string $beskrivelse): void
    {
        $this->beskrivelse = $beskrivelse;
    }

    public function setStatus(string $status): void
    {
        self::krevGyldigStatus($status);
        $this->status = $status;
    }
}
