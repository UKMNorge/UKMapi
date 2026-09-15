<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Arrangement\Oppgave\Oppgave;

require_once('UKM/Autoloader.php');

/**
 * SMS eller beskjed knyttet til en oppgave.
 */
class OppgaveBeskjed extends BeskjedSuper
{
    const TABLE = 'skjema_oppgave_beskjed';
    const PARENT_ID_COLUMN = 'oppgave_id';
    const PARENT_CLASS = Oppgave::class;

    public static function getTable(): string
    {
        return self::TABLE;
    }

    public static function getParentIdColumn(): string
    {
        return self::PARENT_ID_COLUMN;
    }

    public static function getParentClass(): string
    {
        return self::PARENT_CLASS;
    }

    public function getOppgaveId(): int
    {
        return $this->getParentId();
    }

    /**
     * @param int|Oppgave $oppgave
     * @return self[]
     */
    public static function getAllForOppgave($oppgave, ?string $rolle = null): array
    {
        return parent::getAllFor($oppgave, $rolle);
    }

    /**
     * @param int|Oppgave $oppgave
     */
    public static function getSisteForOppgave($oppgave, string $rolle): ?self
    {
        return parent::getSisteFor($oppgave, $rolle);
    }
}
