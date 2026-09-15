<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Database\SQL\Query;

use Exception;

require_once('UKM/Autoloader.php');

/**
 * Superklasse for SMS/beskjed knyttet til et skjema-svar eller en oppgave.
 *
 * Subklasser må implementere getTable(), getParentIdColumn() og getParentClass().
 */
abstract class BeskjedSuper implements BeskjedInterface
{
    const ROLLE_DELTAKER = 'deltaker';
    const ROLLE_FORESATT = 'foresatt';
    const VENTETID_SEKUNDER = 86400;

    protected $id;
    protected $parentId;
    protected $createdAt;
    protected $rolle;
    protected $message;
    protected $phone;

    /**
     * Opprett fra ID eller rad-array
     * @param int|array $data
     * @throws Exception
     */
    public function __construct($data)
    {
        if (is_numeric($data)) {
            $this->_loadById((int) $data);
        } elseif (is_array($data)) {
            $this->_loadByRow($data);
        } else {
            throw new Exception('Kan kun opprette ' . static::class . ' med numerisk ID eller rad fra database.');
        }
    }

    /**
     * Last inn fra ID
     * @throws Exception
     */
    protected function _loadById(int $id): void
    {
        $sql = new Query(
            "SELECT * FROM `" . static::getTable() . "` WHERE `id` = '#id'",
            ['id' => $id]
        );
        $row = $sql->run('array');
        if (!$row) {
            throw new Exception('Fant ikke ' . static::class . " med ID $id");
        }
        $this->_loadByRow($row);
    }

    /**
     * Last inn fra rad-array
     */
    protected function _loadByRow(array $row): void
    {
        $this->id        = (int) $row['id'];
        $this->parentId  = (int) $row[static::getParentIdColumn()];
        $this->createdAt = $row['created_at'] ?? null;
        $this->rolle     = $row['rolle'];
        $this->message   = $row['message'];
        $this->phone     = $row['phone'];
    }

    /**
     * Hent alle beskjeder for parent, nyeste først.
     *
     * @param int|object $parent
     * @return static[]
     */
    public static function getAllFor($parent, ?string $rolle = null): array
    {
        $parentCol = static::getParentIdColumn();
        $params = [$parentCol => static::resolveParentId($parent)];
        $rolleFilter = '';

        if ($rolle !== null) {
            $params['rolle'] = static::validateRolle($rolle);
            $rolleFilter = ' AND `rolle` = \'#rolle\'';
        }

        $sql = new Query(
            "SELECT *
             FROM `" . static::getTable() . "`
             WHERE `" . $parentCol . "` = '#" . $parentCol . "'" . $rolleFilter . "
             ORDER BY `created_at` DESC, `id` DESC",
            $params
        );

        $beskjeder = [];
        $res = $sql->run();
        while ($row = Query::fetch($res)) {
            $beskjeder[] = new static($row);
        }

        return $beskjeder;
    }

    /**
     * Hent siste beskjed for parent og en rolle.
     *
     * @param int|object $parent
     * @return static|null
     */
    public static function getSisteFor($parent, string $rolle): ?static
    {
        $parentCol = static::getParentIdColumn();

        $sql = new Query(
            "SELECT *
             FROM `" . static::getTable() . "`
             WHERE `" . $parentCol . "` = '#" . $parentCol . "'
               AND `rolle` = '#rolle'
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT 1",
            [
                $parentCol => static::resolveParentId($parent),
                'rolle' => static::validateRolle($rolle),
            ]
        );

        $row = $sql->run('array');
        if (!$row) {
            return null;
        }

        return new static($row);
    }

    /**
     * Siste beskjed per telefonnummer (kun siffer). Nyeste først fra getAllFor.
     *
     * @param int|object $parent
     * @return array<string, static>
     */
    public static function getSistePerTelefon($parent, ?string $rolle = null): array
    {
        $siste = [];
        foreach (static::getAllFor($parent, $rolle) as $beskjed) {
            $key = static::normalizePhone($beskjed->getPhone());
            if ($key === '' || isset($siste[$key])) {
                continue;
            }
            $siste[$key] = $beskjed;
        }

        return $siste;
    }

    /**
     * @param array<string, static> $sistePerTelefon
     * @param string[] $telefoner
     */
    public static function velgSisteForTelefoner(array $sistePerTelefon, array $telefoner): ?static
    {
        $valgt = null;
        foreach ($telefoner as $phone) {
            $key = static::normalizePhone((string) $phone);
            if ($key === '' || !isset($sistePerTelefon[$key])) {
                continue;
            }
            $kandidat = $sistePerTelefon[$key];
            if (
                $valgt === null
                || $kandidat->getCreatedAtTimestamp() > $valgt->getCreatedAtTimestamp()
                || (
                    $kandidat->getCreatedAtTimestamp() === $valgt->getCreatedAtTimestamp()
                    && $kandidat->getId() > $valgt->getId()
                )
            ) {
                $valgt = $kandidat;
            }
        }

        return $valgt;
    }

    public static function normalizePhone(string $phone): string
    {
        return (string) preg_replace('/\D/', '', $phone);
    }

    public function erSendtSisteDogn(): bool
    {
        $ts = $this->getCreatedAtTimestamp();
        return $ts > 0 && $ts > (time() - self::VENTETID_SEKUNDER);
    }

    /**
     * @return array{id: int, melding: string, rolle: string, phone: string, created_at: string|null, created_at_ts: int, sendt_siste_dogn: bool}
     */
    public function toArray(): array
    {
        return [
            'id'               => $this->getId(),
            'melding'          => $this->getMessage(),
            'rolle'            => $this->getRolle(),
            'phone'            => $this->getPhone(),
            'created_at'       => $this->getCreatedAt(),
            'created_at_ts'    => $this->getCreatedAtTimestamp(),
            'sendt_siste_dogn' => $this->erSendtSisteDogn(),
        ];
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getParentId(): int
    {
        return (int) $this->parentId;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getCreatedAtTimestamp(): int
    {
        return $this->createdAt ? strtotime($this->createdAt) : 0;
    }

    public function getRolle(): string
    {
        return $this->rolle;
    }

    public function isDeltaker(): bool
    {
        return $this->rolle === self::ROLLE_DELTAKER;
    }

    public function isForesatt(): bool
    {
        return $this->rolle === self::ROLLE_FORESATT;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    /**
     * @param int|object $parent
     */
    protected static function resolveParentId($parent): int
    {
        $parentClass = static::getParentClass();
        if ($parent instanceof $parentClass) {
            return (int) $parent->getId();
        }
        if (is_numeric($parent)) {
            return (int) $parent;
        }

        throw new Exception(
            static::getParentIdColumn() . ' må være numerisk ID eller ' . $parentClass . '.'
        );
    }

    public static function validateRolle(string $rolle): string
    {
        if (!in_array($rolle, [self::ROLLE_DELTAKER, self::ROLLE_FORESATT], true)) {
            throw new Exception("Ugyldig rolle '$rolle'. Må være 'deltaker' eller 'foresatt'.");
        }

        return $rolle;
    }
}
