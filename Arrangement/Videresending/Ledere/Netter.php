<?php

namespace UKMNorge\Arrangement\Videresending\Ledere;

use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class Netter extends Collection {
    public function __construct( Leder $leder ) 
    {
        $this->setId($leder->getId());
    }

    public function _load() {
        $query = new Query(
            "SELECT * 
            FROM `". Natt::TABLE ."` 
            WHERE `l_id` = '#leder'",
            [
                'leder' => $this->getId() // lederId
            ]
        );
        $res = $query->run();

        while( $row = Query::fetch( $res ) ) {
            $this->add(
                Natt::getByDatabaseRow( $row ) 
            );
        }
    }

    /**
     * Hent eller opprett et nattobjekt for gitt dato
     *
     * @param String $id (dato)
     * @return Natt
     */
    public function get($id) {
        $natt = parent::get($id);
        if( !$natt ) {
            $natt = Natt::getEmptyByDato( $this->getId(), $id );
        }
        return $natt;
    }
}