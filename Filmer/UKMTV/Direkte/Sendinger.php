<?php

namespace UKMNorge\Filmer\UKMTV\Direkte;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Sendinger extends Collection
{
    /**
     * Hent alle sendinger for ett arrangement
     *
     * @param Int $arrangement_id
     * @return Sendinger
     */
    public static function getAllByArrangement(Int $arrangement_id)
    {
        $query = new Query(
            "SELECT * FROM `ukm_direkte_view`
            WHERE `arrangement_id` = '#arrangement'",
            ['arrangement' => $arrangement_id]
        );
        $res = $query->run();

        $collection = new Sendinger();
        while ($row = Query::fetch($res)) {
            $collection->add(
                new Sending($row)
            );
        }
        return $collection;
    }

    /**
     * Hent sending for ett gitt arrangement
     *
     * @param Int $arrangement_id
     * @return Sending
     * @throws Exception
     */
    public static function getByHendelse(Int $hendelse_id)
    {
        try {
            return static::getByQuery(
                new Query(
                    "SELECT * FROM `ukm_direkte_view`
                    WHERE `hendelse_id` = '#hendelse'",
                    ['hendelse' => $hendelse_id]
                )
            );
        } catch (Exception $e) {
            // Overskriv selve feilmeldingen hvis 144001 (ikke funnet)
            if ($e->getCode() == 144001) {
                throw new Exception(
                    'Hendelse ' . $hendelse_id . ' har ikke en tilknyttet sending',
                    144001
                );
            }
            throw $e;
        }
    }

    /**
     * Hent sending for gitt spørring
     *
     * @param Query $query
     * @return Sending
     * @throws Exception
     */
    public static function getByQuery(Query $query)
    {
        $res = $query->getArray();
        if (!$res) {
            throw new Exception(
                'Fant ikke sending',
                144001
            );
        }
        return new Sending($res);
    }

    /**
     * Hent sending for gitt id
     *
     * @param Int $id
     * @return Sending
     * @throws Exception
     */
    public static function getById(Int $id)
    {
        return static::getByQuery(
            new Query(
                "SELECT * FROM `ukm_direkte_view`
                WHERE `id` = '#id'
                LIMIT 1",
                ['id' => $id]
            )
        );
    }
}
