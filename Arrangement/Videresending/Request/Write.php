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
    public static function createOrUpdate(RequestVideresending $reqVideresending)
    {
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
}
