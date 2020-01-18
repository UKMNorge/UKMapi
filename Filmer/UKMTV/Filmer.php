<?php

namespace UKMNorge\Filmer\UKMTV;

use UKMNorge\Collection;
use Exception;
use UKMNorge\Database\SQL\Query;

class Filmer extends Collection
{
    public $query;
    /**
     * Henter inn filmer basert på gitt spørring (via constructor)
     *
     * @return bool true
     * @throws Exception
     */
    public function _load()
    {
        $res = $this->query->run();
        if( !$res ) {
            throw new Exception(
                'Kunne ikke laste inn filmer, grunnet databasefeil',
                115001
            );
        }
        while ($filmData = Query::fetch($res)) {
            $film = new Film($filmData);
            if( $film->erSlettet() ) {
                continue;
            }
            $this->add($film);
        }

        return true;
    }
    
    /**
     * Opprett en ny samling filmer
     *
     * @param Query Spørring for å hente ut filmer
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public static function getById( Int $tv_id ) {
        $query = new Query(
            Film::getLoadQuery() ."
            WHERE `tv_id` = '#tvid'
            AND `tv_deleted` = 'false'",
            [
                'tvid' => $tv_id
            ]
            );
        $data = $query->getArray();
        if( !$data ) {
            throw new Exception(
                'Beklager! Klarte ikke å finne film '. intval($tv_id),
                115007
            );
        }
        return new Film( $data );
    }
}
