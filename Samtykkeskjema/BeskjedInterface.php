<?php

namespace UKMNorge\Samtykkeskjema;

interface BeskjedInterface
{
    public static function getTable(): string;

    public static function getOwnerIdColumn(): string;

    public static function getOwnerClass(): string;
}
