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
    const OWNER_ID_COLUMN = 'oppgave_id';
    const OWNER_CLASS = Oppgave::class;

    public static function getTable(): string
    {
        return self::TABLE;
    }

    public static function getOwnerIdColumn(): string
    {
        return self::OWNER_ID_COLUMN;
    }

    public static function getOwnerClass(): string
    {
        return self::OWNER_CLASS;
    }

    public function getOppgaveId(): int
    {
        return $this->getOwnerId();
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

    /**
     * @param int|Oppgave $oppgave
     * @return array<string, self>
     */
    public static function getSistePerTelefonForOppgave($oppgave, ?string $rolle = null): array
    {
        return parent::getSistePerTelefon($oppgave, $rolle);
    }

    /**
     * @param int|Oppgave $oppgave
     */
    /**
     * Hent siste beskjed for en oppgave, et telefonnummer og en rolle.
     * 
     * $rolle kan være BeskjedSuper::ROLLE_DELTAKER eller BeskjedSuper::ROLLE_FORESATT
     *
     * @param int|Oppgave $oppgave
     * @param string $phone
     * @param string $rolle BeskjedSuper::ROLLE_DELTAKER eller BeskjedSuper::ROLLE_FORESATT
     * @return self|null
     */
    public static function getSisteByTelefonForOppgave($oppgave, string $phone, string $rolle): ?self
    {   
        return parent::getSisteForTelefon($oppgave, $phone, $rolle);
    }

    /**
     * @param int|Oppgave $oppgave
     * @return array{deltaker: array<string, self>, foresatt: array<string, self>}
     */
    public static function getSistePerTelefonEtterRolleForOppgave($oppgave): array
    {
        return parent::getSistePerTelefonEtterRolle($oppgave);
    }
}
