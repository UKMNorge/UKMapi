<?php

namespace UKMNorge\Arrangement\Oppgave;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;

class Oppgave {
    public const TABLE = 'oppgave';

    public const TYPE_VIDERESENDING = 'videresending';
    public const TYPE_REISELEDERE = 'reiseledere';
    public const TYPE_FYLKESKONTAKTER = 'fylkeskontakter';

    private int $id;
    private string $name;
    private ?string $type;
    private int $plId;
    private ?string $description;
    private bool $locked = false;

    /** @var array<int, OppgaveSkjema>|null */
    private ?array $skjemaKjede = null;

    public function __construct($idOrRow) {
        if (is_numeric($idOrRow)) {
            $this->_loadById((int) $idOrRow);
        } elseif (is_array($idOrRow)) {
            $this->_loadByRow($idOrRow);
        } else {
            throw new Exception('Oppgave: Oppretting krever numerisk id eller databaserad');
        }
    }

    public static function getLoadSql(): string {
        return 'SELECT * FROM `' . self::TABLE . '` AS `oppgave`';
    }

    private function _loadById(int $id): void {
        $qry = new Query(
            self::getLoadSql() . ' WHERE `oppgave`.`id` = \'#id\'',
            ['id' => $id]
        );
        $res = $qry->run('array');
        if ($res) {
            $this->_loadByRow($res);
        } else {
            throw new Exception('Oppgave: Fant ikke oppgave ' . $id);
        }
    }

    private function _loadByRow(array $row): void {
        $this->id = (int) $row['id'];
        $this->name = (string) $row['name'];
        $this->type = isset($row['type']) && $row['type'] !== null && $row['type'] !== ''
            ? (string) $row['type']
            : null;
        $this->plId = (int) $row['pl_id'];
        $this->description = isset($row['description']) ? $row['description'] : null;
        $this->locked = isset($row['locked']) ? ((int) $row['locked'] === 1) : false;
        $this->skjemaKjede = null;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public function getType(): ?string {
        return $this->type;
    }

    public function getPlId(): int {
        return $this->plId;
    }

    public function getDescription(): ?string {
        return $this->description;
    }

    public function isLocked(): bool {
        return $this->locked;
    }

    public function getArrangement(): Arrangement {
        return new Arrangement($this->plId);
    }

    /**
     * Alle oppgave_skjema-rader for denne oppgaven, i rekkefølge fra hode til hale.
     * Kjeden bygges via neste_type/neste_id som peker på neste skjema (type+id).
     *
     * @return OppgaveSkjema[]
     */
    public function getSkjemaKjede(): array {
        if ($this->skjemaKjede !== null) {
            return $this->skjemaKjede;
        }

        $bySkjema = OppgaveSkjema::mapBySkjemaForOppgave($this->id);
        OppgaveSkjema::kobleKjede($bySkjema);

        $targets = [];
        foreach ($bySkjema as $node) {
            if ($node->getNesteType() !== null && $node->getNesteId() !== null) {
                $targets[OppgaveSkjema::skjemaNokkel($node->getNesteType(), $node->getNesteId())] = true;
            }
        }

        $heads = [];
        foreach ($bySkjema as $key => $node) {
            if (!isset($targets[$key])) {
                $heads[] = $node;
            }
        }

        usort($heads, static function (OppgaveSkjema $a, OppgaveSkjema $b): int {
            return $a->getId() <=> $b->getId();
        });

        $ordered = [];
        $seen = [];
        foreach ($heads as $head) {
            $cur = $head;
            while ($cur !== null) {
                $k = OppgaveSkjema::skjemaNokkel($cur->getSkjemaType(), $cur->getSkjemaId());
                if (isset($seen[$k])) {
                    break;
                }
                $seen[$k] = true;
                $ordered[] = $cur;
                $cur = $cur->getNeste();
            }
        }

        if (count($ordered) < count($bySkjema)) {
            $rest = [];
            foreach ($bySkjema as $key => $node) {
                if (!isset($seen[$key])) {
                    $rest[] = $node;
                }
            }
            usort($rest, static function (OppgaveSkjema $a, OppgaveSkjema $b): int {
                return $a->getId() <=> $b->getId();
            });
            foreach ($rest as $node) {
                $ordered[] = $node;
            }
        }

        $this->skjemaKjede = $ordered;
        return $this->skjemaKjede;
    }

    /**
     * Første skjema i kjeden, eller null hvis ingen rader.
     */
    public function getForsteSkjema(): ?OppgaveSkjema {
        $kjede = $this->getSkjemaKjede();
        return $kjede[0] ?? null;
    }

    public function harOppgaveSkjema(): bool {
        return count($this->getSkjemaKjede()) > 0;
    }

    /**
     * @return self[]
     */
    public static function getAllByArrangement(int $plId): array {
        $sql = new Query(
            self::getLoadSql() . ' WHERE `oppgave`.`pl_id` = \'#plId\' ORDER BY `oppgave`.`id` ASC',
            ['plId' => $plId]
        );
        $res = $sql->run();
        $list = [];
        while ($row = Query::fetch($res)) {
            $list[] = new self($row);
        }
        return $list;
    }
}
