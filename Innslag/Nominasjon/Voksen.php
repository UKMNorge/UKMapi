<?php

namespace UKMNorge\Innslag\Nominasjon;

use UKMNorge\Database\SQL\Query;
use Exception;

class Voksen extends PlaceholderVoksen
{
    /**
     * Hent inn voksen-objektet til en nominasjon
     *
     * @param Int $nominasjon_id
     * @return Voksen
     */
    public function __construct(Int $nominasjon_id)
    {
        if (!is_numeric($nominasjon_id)) {
            throw new Exception(
                'Trenger numerisk nominasjons-ID for å opprette voksen',
                122001
            );
        }

        $sql = new Query(
            "SELECT * 
			FROM `ukm_nominasjon_voksen`
			WHERE `nominasjon` = '#nominasjon'",
            ['nominasjon' => $nominasjon_id]
        );
        $res = $sql->getArray();

        if (is_null($res)) {
            throw new Exception(
                'Kunne ikke finne voksen for nominasjon ' . $nominasjon_id,
                122002
            );
        }

        $this->setId(intval($res['id']));
        $this->setNominasjon($res['nominasjon']);
        $this->setNavn($res['navn']);
        $this->setMobil(intval($res['mobil']));
        $this->setRolle($res['rolle']);
    }
}
