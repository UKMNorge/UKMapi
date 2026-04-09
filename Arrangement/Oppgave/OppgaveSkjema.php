<?php

namespace UKMNorge\Arrangement\Oppgave;

use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Samtykkeskjema\SkjemaSuper;
use UKMNorge\Samtykkeskjema\SamtykkeSkjema;
use UKMNorge\Arrangement\Skjema\Skjema;

class OppgaveSkjema {
    public const TABLE = 'oppgave_skjema';

    public const SKJEMA_SAMTYKKE = 'samtykkeskjema';
    public const SKJEMA_VIDERESENDING = 'ukm_videresending_skjema'; // TODO: Endre til 'sporreskjema' - Dette er engentlig ikke riktig navn

    private int $id;
    private int $oppgaveId;
    private string $skjemaType;
    private int $skjemaId;
    private ?string $nesteType;
    private ?int $nesteId;
    private ?OppgaveSkjema $nesteNode = null;

    public function __construct($idOrRow) {
        if (is_numeric($idOrRow)) {
            $this->_loadById((int) $idOrRow);
        } elseif (is_array($idOrRow)) {
            $this->_loadByRow($idOrRow);
        } else {
            throw new Exception('OppgaveSkjema: Oppretting krever numerisk id eller databaserad');
        }
    }

    public static function getLoadSql(): string {
        return 'SELECT * FROM `' . self::TABLE . '` AS `oppgave_skjema`';
    }

    private function _loadById(int $id): void {
        $qry = new Query(
            self::getLoadSql() . ' WHERE `oppgave_skjema`.`id` = \'#id\'',
            ['id' => $id]
        );
        $res = $qry->run('array');
        if ($res) {
            $this->_loadByRow($res);
        } else {
            throw new Exception('OppgaveSkjema: Fant ikke rad ' . $id);
        }
    }

    private function _loadByRow(array $row): void {
        $this->id = (int) $row['id'];
        $this->oppgaveId = (int) $row['oppgave_id'];
        $this->skjemaType = (string) $row['skjema_type'];
        $this->skjemaId = (int) $row['skjema_id'];
        $this->nesteType = isset($row['neste_type']) && $row['neste_type'] !== null && $row['neste_type'] !== ''
            ? (string) $row['neste_type']
            : null;
        $this->nesteId = isset($row['neste_id']) && $row['neste_id'] !== null && $row['neste_id'] !== ''
            ? (int) $row['neste_id']
            : null;
    }

    /**
     * Kalles fra Oppgave ved oppbygging av kjeden.
     */
    public function bindNeste(?self $node): void {
        $this->nesteNode = $node;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getOppgaveId(): int {
        return $this->oppgaveId;
    }

    public function getSkjemaType(): string {
        return $this->skjemaType;
    }

    public function getSkjemaTypeLabel(): string {
        if($this->skjemaType == self::SKJEMA_SAMTYKKE) {
            return 'Samtykke';
        }
        else if($this->skjemaType == self::SKJEMA_VIDERESENDING) {
            return 'Sporreskjema';
        }
        return 'Ukjent';
    }

    public function getSkjema() : SkjemaSuper {
        if($this->skjemaType == self::SKJEMA_SAMTYKKE) {
            return new SamtykkeSkjema($this->skjemaId);
        }
        else if($this->skjemaType == self::SKJEMA_VIDERESENDING) {
            return Skjema::getById($this->skjemaId);
        }
        throw new Exception('OppgaveSkjema: Ugyldig skjema type ' . $this->skjemaType);
    }

    public function getSkjemaId(): int {
        return $this->skjemaId;
    }

    public function getNesteType(): ?string {
        return $this->nesteType;
    }

    public function getNesteId(): ?int {
        return $this->nesteId;
    }

    /**
     * Neste ledd i kjeden (samme oppgave), eller null hvis siste / brutt referanse.
     */
    public function getNeste(): ?self {
        return $this->nesteNode;
    }

    public static function skjemaNokkel(string $type, int $id): string {
        return $type . ':' . $id;
    }

    /**
     * @return array<string, self>
     */
    public static function mapBySkjemaForOppgave(int $oppgaveId): array {
        $sql = new Query(
            self::getLoadSql() . ' WHERE `oppgave_skjema`.`oppgave_id` = \'#oppgaveId\'',
            ['oppgaveId' => $oppgaveId]
        );
        $res = $sql->run();
        $map = [];
        while ($row = Query::fetch($res)) {
            $node = new self($row);
            $map[self::skjemaNokkel($node->getSkjemaType(), $node->getSkjemaId())] = $node;
        }
        return $map;
    }

    /**
     * Kobler neste-pekere ut fra neste_type/neste_id -> neste rads skjema_type/skjema_id.
     *
     * @param array<string, self> $bySkjema
     */
    public static function kobleKjede(array $bySkjema): void {
        foreach ($bySkjema as $node) {
            $neste = null;
            if ($node->getNesteType() !== null && $node->getNesteId() !== null) {
                $key = self::skjemaNokkel($node->getNesteType(), $node->getNesteId());
                $neste = $bySkjema[$key] ?? null;
            }
            $node->bindNeste($neste);
        }
    }
}
