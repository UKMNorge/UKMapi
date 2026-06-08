<?php

namespace UKMNorge\Arrangement\Oppgave;

use Exception;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Videresending\VideresendingNominasjoner;
use UKMNorge\Arrangement\Skjema\DeltaRespondent;
use UKMNorge\Arrangement\Videresending\Ledere\Ledere;

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
     * Returnerer alle respondenter for alle skjemaer i oppgaven
     * @return array<int, DeltaRespondent> - Delta-bruker-id som nøkkel, DeltaRespondent som verdi
     */
    public function getAlleRespondenter($withVideresending = true, $arrangementId = null): array {
        $respondenter = [];

        if($this->getType() === self::TYPE_REISELEDERE) {
            $alleVideresendteArrangementer = $this->getArrangement()->getVideresending()->getAvsendere();
            foreach($alleVideresendteArrangementer as $fraArrangement) {
                if($arrangementId && ($fraArrangement->getId() !== $arrangementId)) {
                    continue;
                }
                $reiseledere = new Ledere($fraArrangement->getId(), $this->getArrangement()->getId());
                foreach($reiseledere->getAll() as $reiseleder) {
                    $respondent = DeltaRespondent::loadByMobil($reiseleder->getMobil());
                    if(!$respondent) {
                        $respondenter[$reiseleder->getMobil()] = DeltaRespondent::getWithoutExisting($reiseleder->getNavn(), '', $reiseleder->getMobil());
                    }
                    else {
                        $respondenter[$reiseleder->getMobil()] = $respondent;
                    }
                }
            }
        }
        else if($withVideresending && $this->getType() === self::TYPE_VIDERESENDING) {
            $videresendingNominasjoner = VideresendingNominasjoner::getAlleTilArrangement($this->getArrangement()->getId())->getAll();
            foreach($videresendingNominasjoner as $videresendingNominasjon) {
                if($arrangementId && ($videresendingNominasjon->getArrangementFra()->getId() !== $arrangementId)) {
                    continue;
                }
                $respondent = $videresendingNominasjon->getPerson();
                if(!$respondent) {
                    continue;
                }
                $deltaRespondent = DeltaRespondent::loadByMobil($respondent->getMobil());
                if($deltaRespondent) {
                    $respondenter[$deltaRespondent->getId()] = $deltaRespondent;
                    $deltaRespondent->videresending_nominasjon = true;
                    $arrangementFra = $videresendingNominasjon->getArrangementFra();
                    $deltaRespondent->fylke = $arrangementFra->getFylke()->getNavn();
                    $deltaRespondent->arrangement = $arrangementFra->getNavn();
                }
            }
        }
        else {
            foreach ($this->getSkjemaKjede() as $skjema) {
                foreach ($skjema->getSkjema()->getAlleRespondenter() as $respondentId => $respondent) {
                    $respondenter[$respondent->getMobil()] = $respondent;
                }
            }
        }

        return $respondenter;
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

    public static function getAllByArrangementVideresending(int $plId): array {
        $alleOppgaver = static::getAllByArrangement($plId);
        $videresendingSkjemaer = [];
        foreach ($alleOppgaver as $oppgave) {
            if ($oppgave->getType() === self::TYPE_VIDERESENDING) {
                $videresendingSkjemaer[] = $oppgave;
            }
        }
        return $videresendingSkjemaer;
    }

    /**
     * Alle oppgaver (uavhengig av opprettelses-arrangement) der minst én respondent
     * har besvart og har et innslag (smartukm_band) påmeldt `$plId`.
     *
     * @return self[]
     */
    public static function getAlleByRespondentArrangement(int $plId): array {
        $oppgaveIdMap = [];

        $sporreskjemaSql = new Query(
            "SELECT DISTINCT `oppgave_skjema`.`oppgave_id`
            FROM `oppgave_skjema`
            JOIN `ukm_videresending_skjema_svar` AS `svar`
                ON `svar`.`skjema` = `oppgave_skjema`.`skjema_id`
            JOIN `smartukm_rel_b_p` AS `rbp`
                ON `rbp`.`p_id` = `svar`.`p_fra`
            JOIN `ukm_rel_arrangement_innslag` AS `rai`
                ON `rai`.`innslag_id` = `rbp`.`b_id`
            JOIN `smartukm_band` AS `band`
                ON `band`.`b_id` = `rai`.`innslag_id`
                AND `band`.`b_status` = 8
            WHERE `oppgave_skjema`.`skjema_type` = '#sporreskjemaType'
            AND `rai`.`arrangement_id` = '#plId'",
            [
                'plId' => $plId,
                'sporreskjemaType' => OppgaveSkjema::SKJEMA_VIDERESENDING,
            ]
        );
        $res = $sporreskjemaSql->run();
        while ($row = Query::fetch($res)) {
            $oppgaveIdMap[(int) $row['oppgave_id']] = true;
        }

        $phoneSql = new Query(
            "SELECT DISTINCT `p`.`p_phone`
            FROM `ukm_rel_arrangement_innslag` AS `rai`
            JOIN `smartukm_band` AS `band`
                ON `band`.`b_id` = `rai`.`innslag_id`
                AND `band`.`b_status` = 8
            JOIN `smartukm_rel_b_p` AS `rbp`
                ON `rbp`.`b_id` = `band`.`b_id`
            JOIN `smartukm_participant` AS `p`
                ON `p`.`p_id` = `rbp`.`p_id`
            WHERE `rai`.`arrangement_id` = '#plId'
            AND `p`.`p_phone` IS NOT NULL
            AND `p`.`p_phone` != ''",
            ['plId' => $plId]
        );
        $res = $phoneSql->run();
        $phones = [];
        while ($row = Query::fetch($res)) {
            $phones[] = $row['p_phone'];
        }

        if ($phones !== []) {
            $deltaSql = new Query(
                "SELECT `id` FROM `ukm_user` WHERE `phone` IN (#phones)",
                ['phones' => "'" . implode("','", $phones) . "'"],
                'ukmdelta'
            );
            $res = $deltaSql->run();
            $deltaUserIds = [];
            while ($row = Query::fetch($res)) {
                $deltaUserIds[] = (int) $row['id'];
            }

            if ($deltaUserIds !== []) {
                $samtykkeSql = new Query(
                    "SELECT DISTINCT `oppgave_skjema`.`oppgave_id`
                    FROM `oppgave_skjema`
                    JOIN `samtykkeskjema_version` AS `version`
                        ON `version`.`skjema_id` = `oppgave_skjema`.`skjema_id`
                    JOIN `skjema_svar` AS `svar`
                        ON `svar`.`version_id` = `version`.`id`
                    WHERE `oppgave_skjema`.`skjema_type` = '#samtykkeType'
                    AND `svar`.`user` IN (#userIds)",
                    [
                        'samtykkeType' => OppgaveSkjema::SKJEMA_SAMTYKKE,
                        'userIds' => implode(',', $deltaUserIds),
                    ]
                );
                $res = $samtykkeSql->run();
                while ($row = Query::fetch($res)) {
                    $oppgaveIdMap[(int) $row['oppgave_id']] = true;
                }
            }
        }

        if ($oppgaveIdMap === []) {
            return [];
        }

        $sql = new Query(
            self::getLoadSql() . "
            WHERE `oppgave`.`id` IN (#oppgaveIds)
            ORDER BY `oppgave`.`id` ASC",
            ['oppgaveIds' => implode(',', array_keys($oppgaveIdMap))]
        );
        $res = $sql->run();
        $result = [];
        while ($row = Query::fetch($res)) {
            $result[] = new self($row);
        }
        return $result;
    }


    /**
     * Returnerer status for besvaring av oppgave-skjema-kjeden.
     * 0 = Ikke påbegynt
     * 1 = Påbegynt, men ikke alle skjema er besvart
     * 2 = Påbegynt, og alle skjema er besvart, men ikke alle er godkjent av foresatt (for under 18 år)
     * 3 = Alle skjema er besvart, og (enten bruker er 18 år+, eller foresatt har godkjent alt)
     */
    public function getOppgaveBesvartStatus(int $deltaUserId, int $personId): int {
        $skjemaKjede = $this->getSkjemaKjede();
        $alleBesvart = true;
        $alleForesattGodkjent = true;
        $harPaabegynt = false;
        foreach ($skjemaKjede as $skjemaItem) {
            $skjema = $skjemaItem->getSkjema();
            if ($skjema->isAnswered($deltaUserId, $personId)) {
                $harPaabegynt = true;
            }
            else {
                $alleBesvart = false;
            }
            if (!$skjema->isForesattGodkjent($deltaUserId, $personId)) {
                $alleForesattGodkjent = false;
            }
        }

        if (!$harPaabegynt) {
            return 0;
        } elseif (!$alleBesvart) {
            return 1;
        } elseif (!$alleForesattGodkjent) {
            return 2;
        } else {
            return 3;
        }
    }

    public function getOppgaveBesvartStatusByMobil($mobil): int {
        $deltaUserId = $this->getDeltaUserIdByMobil($mobil);
        if($deltaUserId === -1) {
            return -1;
        }
        $personIds = $this->getPersonIdsByMobil($mobil);
        $maxStatus = 0;

        foreach ($personIds as $personId) {
            $status = $this->getOppgaveBesvartStatus($deltaUserId, $personId);
            if ($status > $maxStatus) {
                $maxStatus = $status;
            }
        }
        return $maxStatus;
    }

    /**
     * Velger person_id med høyest besvaringsstatus (samme logikk som status-endepunkt).
     */
    public function getBestPersonIdForRespondent(int $deltaUserId, string $mobil): int {
        $personIds = $this->getPersonIdsByMobil($mobil);
        if (count($personIds) === 0) {
            return 0;
        }
        $bestPersonId = (int) $personIds[0];
        $maxStatus = $this->getOppgaveBesvartStatus($deltaUserId, $bestPersonId);
        foreach ($personIds as $personId) {
            $status = $this->getOppgaveBesvartStatus($deltaUserId, (int) $personId);
            if ($status > $maxStatus) {
                $maxStatus = $status;
                $bestPersonId = (int) $personId;
            }
        }
        return $bestPersonId;
    }

    /**
     * Sjekker om en person identifisert med mobilnummer har tilgang til å besvare oppgaven
     *
     * @param string $phone
     * @return boolean
     */
    public function isRespondentAllowedToAccessOppgave(string $phone): bool {
        // Oppgaven er for videresending, sjekk om respondenten er videresendt til dette arrangementet
        if($this->getType() === self::TYPE_VIDERESENDING) {
            $videresendingNominasjoner = VideresendingNominasjoner::getAlleTilArrangement($this->getArrangement()->getId())->getAll();
            foreach($videresendingNominasjoner as $videresendingNominasjon) {
                $person = $videresendingNominasjon->getPerson();
                if($person && $person->getMobil() == $phone) {
                    return true;
                }
            }
        } 
        // Oppgaven er for reiseledere, sjekk om mobilen til respondenten er i listen av reiseledere for dette arrangementet
        elseif($this->getType() === self::TYPE_REISELEDERE) {
            $alleVideresendteArrangementer = $this->getArrangement()->getVideresending()->getAvsendere();
            foreach($alleVideresendteArrangementer as $fraArrangement) {
                $reiseledere = new Ledere($fraArrangement->getId(), $this->getArrangement()->getId());
                foreach($reiseledere->getAll() as $reiseleder) {
                    if($reiseleder->getGodkjent() == 1) {
                        if($reiseleder->getMobil() == $phone) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Oppgaveliste + svar for én respondent (admin, kun visning).
     */
    public function getRespondentOppgaveliste(DeltaRespondent $respondent): array {
        return OppgaveRespondentVisning::forRespondent($this, $respondent);
    }

    private function getPersonIdsByMobil(string $mobil): array {
   
        $sql = new Query(
            "SELECT p_id FROM `smartukm_participant`
						WHERE `p_phone` = '#mobil'",
            ['mobil' => $mobil],
        );
        $res = $sql->run();
        $personIds = [];
        while ($row = Query::fetch($res)) {
            $personIds[] = $row['p_id'];
        }
        return $personIds;
    }

    private function getDeltaUserIdByMobil(string $mobil): int {
        $sql = new Query(
            "SELECT id from ukm_user WHERE phone = '#phone'",
            ['phone' => $mobil],
            'ukmdelta'
        );
        $res = $sql->run('array');
        return $res['id'] ?? -1;
    }
}
