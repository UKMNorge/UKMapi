<?php

namespace UKMNorge\Filmer\UKMTV;

use Collection;
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
}
