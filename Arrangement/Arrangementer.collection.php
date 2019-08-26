<?php

namespace UKMNorge\Arrangement;
use UKMNorge\Database\SQL\Query;

class Arrangementer {
    public function __construct( $season, $omrade_type, $omrade_id ) {
        switch( $omrade_type ) {
            case 'kommune':
                $query = "AND `pl_kommune` = '#kommune'";
                break;
            case 'fylke':
                $query = "AND `pl_fylke` = '#fylke'";
            break;
            case 'land':

            break;
        }
        $sql = new Query(
            Arrangement::getLoadQry()
            . "WHERE `pl_type` = '#type".
            $query,
            [
                'type' => $omrade_type
            ]
        );

        return $sql;
    }
}