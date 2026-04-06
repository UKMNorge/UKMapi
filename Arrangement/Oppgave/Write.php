<?php

namespace UKMNorge\Arrangement\Oppgave;

use Exception;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;

class Write {

    public static function createOppgave(
        string $name,
        int $plId,
        ?string $type = null,
        ?string $description = null
    ): Oppgave {
        $sql = new Insert(Oppgave::TABLE);
        $sql->add('name', $name);
        $sql->add('pl_id', $plId);
        $sql->add('type', $type);
        $sql->add('description', $description);

        try {
            $id = $sql->run();
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . ' (' . $e->getCode() . ')');
        }

        if (!$id) {
            throw new Exception('Klarte ikke å opprette oppgave', 512001);
        }

        return new Oppgave((int) $id);
    }

    /**
     * Oppretter oppgave og første oppgave_skjema (tom kjede = kun dette leddet).
     */
    public static function createOppgaveMedSkjema(
        string $name,
        int $plId,
        string $skjemaType,
        int $skjemaId,
        ?string $oppgaveType = null,
        ?string $description = null,
        ?string $nesteType = null,
        ?int $nesteId = null
    ): Oppgave {
        $oppgave = self::createOppgave($name, $plId, $oppgaveType, $description);
        self::createOppgaveSkjema($oppgave->getId(), $skjemaType, $skjemaId, $nesteType, $nesteId);
        return new Oppgave($oppgave->getId());
    }

    public static function updateOppgave(
        int $id,
        string $name,
        int $plId,
        ?string $type,
        ?string $description
    ): Oppgave {
        $sql = new Update(
            Oppgave::TABLE,
            ['id' => $id]
        );
        $sql->add('name', $name);
        $sql->add('pl_id', $plId);
        $sql->add('type', $type);
        $sql->add('description', $description);

        try {
            $sql->run();
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . ' (' . $e->getCode() . ')');
        }

        return new Oppgave($id);
    }

    public static function deleteOppgave(Oppgave $oppgave): bool {
        $delete = new Delete(
            Oppgave::TABLE,
            ['id' => $oppgave->getId()]
        );
        $res = $delete->run();
        if (!$res || $res < 1) {
            throw new Exception('Kunne ikke slette oppgave fra databasen', 512002);
        }
        return true;
    }

    public static function createOppgaveSkjema(
        int $oppgaveId,
        string $skjemaType,
        int $skjemaId,
        ?string $nesteType = null,
        ?int $nesteId = null
    ): OppgaveSkjema {
        $sql = new Insert(OppgaveSkjema::TABLE);
        $sql->add('oppgave_id', $oppgaveId);
        $sql->add('skjema_type', $skjemaType);
        $sql->add('skjema_id', $skjemaId);
        $sql->add('neste_type', $nesteType);
        $sql->add('neste_id', $nesteId);

        try {
            $id = $sql->run();
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . ' (' . $e->getCode() . ')');
        }

        if (!$id) {
            throw new Exception('Klarte ikke å opprette oppgave_skjema', 512003);
        }

        return new OppgaveSkjema((int) $id);
    }

    public static function updateOppgaveSkjema(
        int $id,
        string $skjemaType,
        int $skjemaId,
        ?string $nesteType,
        ?int $nesteId
    ): OppgaveSkjema {
        $sql = new Update(
            OppgaveSkjema::TABLE,
            ['id' => $id]
        );
        $sql->add('skjema_type', $skjemaType);
        $sql->add('skjema_id', $skjemaId);
        $sql->add('neste_type', $nesteType);
        $sql->add('neste_id', $nesteId);

        try {
            $sql->run();
        } catch (Exception $e) {
            throw new Exception($e->getMessage() . ' (' . $e->getCode() . ')');
        }

        return new OppgaveSkjema($id);
    }

    public static function deleteOppgaveSkjema(OppgaveSkjema $rad): bool {
        $delete = new Delete(
            OppgaveSkjema::TABLE,
            ['id' => $rad->getId()]
        );
        $res = $delete->run();
        if (!$res || $res < 1) {
            throw new Exception('Kunne ikke slette oppgave_skjema fra databasen', 512004);
        }
        return true;
    }

    /**
     * Legg til skjema sist i kjeden (oppdaterer forrige ledds neste_*).
     */
    public static function appendSkjemaTilKjede(int $oppgaveId, string $skjemaType, int $skjemaId): OppgaveSkjema {
        $oppgave = new Oppgave($oppgaveId);
        $kjede = $oppgave->getSkjemaKjede();
        $ny = self::createOppgaveSkjema($oppgaveId, $skjemaType, $skjemaId, null, null);
        if (count($kjede) > 0) {
            $siste = $kjede[count($kjede) - 1];
            self::updateOppgaveSkjema(
                $siste->getId(),
                $siste->getSkjemaType(),
                $siste->getSkjemaId(),
                $skjemaType,
                $skjemaId
            );
        }
        return $ny;
    }

    /**
     * Fjerner ett ledd og kobler sammen forrige og neste.
     */
    public static function removeOppgaveSkjemaFraKjede(int $oppgaveSkjemaRadId): void {
        $rad = new OppgaveSkjema($oppgaveSkjemaRadId);
        $oppgave = new Oppgave($rad->getOppgaveId());
        $kjede = $oppgave->getSkjemaKjede();
        $idx = null;
        foreach ($kjede as $i => $node) {
            if ($node->getId() === $oppgaveSkjemaRadId) {
                $idx = $i;
                break;
            }
        }
        if ($idx === null) {
            throw new Exception('Fant ikke oppgave_skjema i kjeden', 512005);
        }
        $forrige = $idx > 0 ? $kjede[$idx - 1] : null;
        if ($forrige !== null) {
            self::updateOppgaveSkjema(
                $forrige->getId(),
                $forrige->getSkjemaType(),
                $forrige->getSkjemaId(),
                $rad->getNesteType(),
                $rad->getNesteId()
            );
        }
        self::deleteOppgaveSkjema($rad);
    }

    /**
     * Sett kjedens rekkefølge ut fra `oppgave_skjema.id` (alle rader for oppgaven må være med, ingen ekstra).
     * Oppdaterer `neste_type` / `neste_id` på hver rad.
     *
     * @param int[] $radIdsInOrder
     */
    public static function rekjorKjedeEtterRadIds(int $oppgaveId, array $radIdsInOrder): void {
        $radIdsInOrder = array_map('intval', $radIdsInOrder);

        $map = OppgaveSkjema::mapBySkjemaForOppgave($oppgaveId);
        $fromDb = [];
        foreach ($map as $node) {
            $fromDb[] = $node->getId();
        }
        sort($fromDb);
        $sortedSubmitted = $radIdsInOrder;
        sort($sortedSubmitted);
        if ($fromDb !== $sortedSubmitted) {
            throw new Exception(
                'Rekkefølgen må inneholde nøyaktig alle kjedeelementene for denne oppgaven.',
                512006
            );
        }

        $n = count($radIdsInOrder);
        if ($n === 0) {
            return;
        }

        for ($i = 0; $i < $n; $i++) {
            $cur = new OppgaveSkjema($radIdsInOrder[$i]);
            if ($cur->getOppgaveId() !== $oppgaveId) {
                throw new Exception('Ugyldig oppgave_skjema for denne oppgaven.', 512007);
            }
            $nesteType = null;
            $nesteId = null;
            if ($i < $n - 1) {
                $next = new OppgaveSkjema($radIdsInOrder[$i + 1]);
                $nesteType = $next->getSkjemaType();
                $nesteId = $next->getSkjemaId();
            }
            self::updateOppgaveSkjema(
                $cur->getId(),
                $cur->getSkjemaType(),
                $cur->getSkjemaId(),
                $nesteType,
                $nesteId
            );
        }
    }
}
