<?php

namespace UKMNorge\Samtykkeskjema;

interface BeskjedInterface
{
    public static function getTable(): string;

    public static function getParentIdColumn(): string;

    public static function getParentClass(): string;
}
