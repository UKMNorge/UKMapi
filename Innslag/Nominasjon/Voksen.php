<?php

namespace UKMNorge\Innslag\Nominasjon;


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
            throw new Exception('NOMINASJON_VOKSEN: Trenger numerisk nominasjons-ID for å opprette voksen', 1);
        }

        $sql = new SQL(
            "SELECT * 
			FROM `ukm_nominasjon_voksen`
			WHERE `nominasjon` = '#nominasjon'",
            ['nominasjon' => $nominasjon_id]
        );
        $res = $sql->run('array');

        if (!is_array($res)) {
            throw new Exception('NOMINASJON_VOKSEN: Kunne ikke finne voksen for nominasjon ' . $nominasjon_id, 2);
        }

        $this->setId($res['id']);
        $this->setNominasjon($res['nominasjon']);
        $this->setNavn($res['navn']);
        $this->setMobil($res['mobil']);
        $this->setRolle($res['rolle']);
    }
}
