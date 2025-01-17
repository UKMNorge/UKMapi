<?php

namespace UKMNorge\Nettverk;

use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

use Exception;

class OmradeKontaktpersoner extends Collection {
    public const TABLE = 'ukm_omrade_kontaktperson';
    public const OMRADE_RELATION_TABLE = 'ukm_rel_omrade_kontaktperson_omrade';

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
                JOIN `" . OmradeKontaktpersoner::OMRADE_RELATION_TABLE . "` AS rel 
                    ON rel.kontaktperson_id = kontaktperson.id
                WHERE rel.omrade_id = '#omrade_id' AND 
                rel.omrade_type='#omrade_type' AND
                rel.is_active='1'
                ",
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

    public static function getById($id) {
        $query = new Query(
            "SELECT * FROM `". OmradeKontaktpersoner::TABLE ."` WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );

        $res = $query->run('array');

        if( $res == null ) {
            throw new Exception(
                'Kontaktpersonen finnes ikke',
                562008
            );
        }

        return new OmradeKontaktperson($res);
    }

    public static function getByMobil($mobil) {
        $query = new Query(
            "SELECT * FROM `". OmradeKontaktpersoner::TABLE ."` WHERE `mobil` = '#mobil'",
            [
                'mobil' => $mobil
            ]
        );

        $res = $query->run('array');

        if( $res == null ) {
            throw new Exception(
                'Kontaktpersonen finnes ikke',
                562008
            );
        }

        return new OmradeKontaktperson($res);
    }

    public static function getByEpost($epost) {
        $query = new Query(
            "SELECT * FROM `". OmradeKontaktpersoner::TABLE ."` WHERE `epost` = '#epost'",
            [
                'epost' => $epost
            ]
        );

        $res = $query->run('array');

        if( $res == null ) {
            throw new Exception(
                'Kontaktpersonen finnes ikke',
                562008
            );
        }

        return new OmradeKontaktperson($res);
    }

}
