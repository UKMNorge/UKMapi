<?php

namespace UKMNorge\Arrangement\Videresending\Request;

use Exception;
use UKMNorge\Database\SQL\Common;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;

class Write {

    /**
     * Opprett eller oppdater en RequestVideresending
     *
     * @param RequestVideresending $reqVideresending
     * @return RequestVideresending $reqVideresending
     */
    public static function createOrUpdate(RequestVideresending $reqVideresending) {
        // Sjekker om kombinasjonen arrangement fra til eksisterer
        $reqVideresending = static::eksisterer($reqVideresending);

        if ($reqVideresending->getId() != -1) {
            $query = new Update(
                RequestVideresending::TABLE,
                [
                    'id' => $reqVideresending->getId(),
                ]
            );
        }
        else {
            $query = new Insert(RequestVideresending::TABLE);
        }

        $query->add('arrangement_fra', $reqVideresending->getArrangementFraId());
        $query->add('arrangement_til', $reqVideresending->getArrangementTilId());
        $query->add('dato', $reqVideresending->getDato() ? $reqVideresending->getDato() : date("Y/m/d"));
        $query->add('completed', $reqVideresending->isCompleted() ? 1 : 0);

        $id = $query->run();
        
        $reqVideresending->setId($id);
        
        return $reqVideresending;
    }

    /**
     * Sjekk om kombinasjonen arrangement fra - arrangement til eksisterer
     * Hvis 
     *
     * @param RequestVideresending $reqVideresending
     * @return RequestVideresending $reqVideresending
     */
    public static function eksisterer(RequestVideresending $reqVideresending) {
        $query = new Query(
            "SELECT * 
            FROM `". RequestVideresending::TABLE ."`
            WHERE 
            `arrangement_fra` = '#fra' AND `arrangement_til` = '#til'",
            [
                'fra' => $reqVideresending->getArrangementFraId(),
                'til' => $reqVideresending->getArrangementTilId()
            ]
        );
        
        $res = $query->run('array');
        // Hvis kombinasjonen ble funnet, da settes id på RequestVideresending
        if($res) {
            $reqVideresending->setId($res['id']);
        }

        return $reqVideresending;
    }
}
