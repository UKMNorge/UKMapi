<?php

namespace UKMNorge\Nettverk;

use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

class OmradeKontaktpersoner extends Collection {
    private const TABLE = 'ukm_nettverk_kontaktperson';

    private int $omradeId;
    private string $omradeType;

    /**
     * Opprett en samling ledere
     *
     * @param int $omradeId
     */
    public function __construct(int $omradeId, string $omradeType) {
        $this->omradeId = $omradeId;
        $this->omradeType = $omradeType;
    }


    /**
     * Last inn OmradeKontakperson fra gitt område og områdetype
     *
     * @return void
     */
    public function _load()
    {
        $query = new Query(
            "SELECT *
                FROM `" . OmradeKontaktpersoner::TABLE . "` AS kontaktperson
                JOIN `ukm_rel_nettverk_kontaktperson_omrade` AS rel 
                    ON rel.kontaktperson_id = kontaktperson.id
                WHERE rel.omrade_id = '#omrade_id' and rel.omrade_type='#omrade_type'",
            [
                'omrade_id' => $this->omradeId,
                'omrade_type' => $this->omradeType
            ]
        );

        $res = $query->getResults();
        while( $row = Query::fetch($res) ) {
            $this->add(new OmradeKontaktperson($row));
        }
    }

}
