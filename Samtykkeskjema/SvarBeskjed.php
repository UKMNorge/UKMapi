<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Database\SQL\Query;

use Exception;

require_once('UKM/Autoloader.php');

/**
 * Representerer en SMS eller beskjed sendt til deltaker eller foresatt, knyttet til et skjema_svar.
 */
class SvarBeskjed
{
    const TABLE = 'skjema_svar_beskjed';

    const ROLLE_DELTAKER = 'deltaker';
    const ROLLE_FORESATT = 'foresatt';

    protected $id;
    protected $skjemaSvarId;
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
            throw new Exception('Kan kun opprette SvarBeskjed med numerisk ID eller rad fra database.');
        }
    }

    /**
     * Last inn fra ID
     * @param int $id
     * @throws Exception
     */
    protected function _loadById(int $id): void
    {
        $sql = new Query(
            "SELECT * FROM `" . self::TABLE . "` WHERE `id` = '#id'",
            ['id' => $id]
        );
        $row = $sql->run('array');
        if (!$row) {
            throw new Exception("Fant ikke SvarBeskjed med ID $id");
        }
        $this->_loadByRow($row);
    }

    /**
     * Last inn fra rad-array
     * @param array $row
     */
    protected function _loadByRow(array $row): void
    {
        $this->id           = (int) $row['id'];
        $this->skjemaSvarId = (int) $row['skjema_svar_id'];
        $this->createdAt    = $row['created_at'] ?? null;
        $this->rolle        = $row['rolle'];
        $this->message      = $row['message'];
        $this->phone        = $row['phone'];
    }

    /**
     * Hent alle beskjeder for et skjema_svar, nyeste først.
     *
     * @param int|SvarUser $skjemaSvar
     * @param string|null $rolle Filtrer på 'deltaker' eller 'foresatt'
     * @return self[]
     */
    public static function getAllForSvar($skjemaSvar, ?string $rolle = null): array
    {
        $skjemaSvarId = self::resolveSvarId($skjemaSvar);
        $params = ['skjema_svar_id' => $skjemaSvarId];
        $rolleFilter = '';

        if ($rolle !== null) {
            $params['rolle'] = self::validateRolle($rolle);
            $rolleFilter = ' AND `rolle` = \'#rolle\'';
        }

        $sql = new Query(
            "SELECT *
             FROM `" . self::TABLE . "`
             WHERE `skjema_svar_id` = '#skjema_svar_id'" . $rolleFilter . "
             ORDER BY `created_at` DESC, `id` DESC",
            $params
        );

        $beskjeder = [];
        $res = $sql->run();
        while ($row = Query::fetch($res)) {
            $beskjeder[] = new self($row);
        }

        return $beskjeder;
    }

    /**
     * Hent siste beskjed for et skjema_svar og en rolle.
     *
     * @param int|SvarUser $skjemaSvar
     * @param string $rolle
     * @return self|null
     */
    public static function getSisteForSvar($skjemaSvar, string $rolle): ?self
    {
        $skjemaSvarId = self::resolveSvarId($skjemaSvar);

        $sql = new Query(
            "SELECT *
             FROM `" . self::TABLE . "`
             WHERE `skjema_svar_id` = '#skjema_svar_id'
               AND `rolle` = '#rolle'
             ORDER BY `created_at` DESC, `id` DESC
             LIMIT 1",
            [
                'skjema_svar_id' => $skjemaSvarId,
                'rolle' => self::validateRolle($rolle),
            ]
        );

        $row = $sql->run('array');
        if (!$row) {
            return null;
        }

        return new self($row);
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    public function getSkjemaSvarId(): int
    {
        return (int) $this->skjemaSvarId;
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
     * @param int|SvarUser $skjemaSvar
     */
    private static function resolveSvarId($skjemaSvar): int
    {
        if ($skjemaSvar instanceof SvarUser) {
            return (int) $skjemaSvar->getId();
        }
        if (is_numeric($skjemaSvar)) {
            return (int) $skjemaSvar;
        }

        throw new Exception('skjema_svar må være numerisk ID eller SvarUser.');
    }

    public static function validateRolle(string $rolle): string
    {
        if (!in_array($rolle, [self::ROLLE_DELTAKER, self::ROLLE_FORESATT], true)) {
            throw new Exception("Ugyldig rolle '$rolle'. Må være 'deltaker' eller 'foresatt'.");
        }

        return $rolle;
    }
}
