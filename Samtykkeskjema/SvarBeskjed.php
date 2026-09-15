<?php

namespace UKMNorge\Samtykkeskjema;

require_once('UKM/Autoloader.php');

/**
 * SMS eller beskjed knyttet til et skjema_svar.
 */
class SvarBeskjed extends BeskjedSuper
{
    const TABLE = 'skjema_svar_beskjed';
    const PARENT_ID_COLUMN = 'skjema_svar_id';
    const PARENT_CLASS = SvarUser::class;

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

    public function getSkjemaSvarId(): int
    {
        return $this->getParentId();
    }

    /**
     * @param int|SvarUser $skjemaSvar
     * @return self[]
     */
    public static function getAllForSvar($skjemaSvar, ?string $rolle = null): array
    {
        return parent::getAllFor($skjemaSvar, $rolle);
    }

    /**
     * @param int|SvarUser $skjemaSvar
     */
    public static function getSisteForSvar($skjemaSvar, string $rolle): ?self
    {
        return parent::getSisteFor($skjemaSvar, $rolle);
    }
}
