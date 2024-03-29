<?php

namespace UKMNorge\UKMFestivalen\Overnatting;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Collection;

class OvernattingGruppe extends Collection {
    private $gruppeId;
    private $navn;
    public static $personerTableName = 'ukm_festival_overnatting_person';

    public function __construct($gruppeId, $navn) {
        $this->gruppeId = $gruppeId;
        $this->navn = $navn;

        // Hent alle personer
        $this->_load_from_db();
    }

    /**
     * Hent id
     *
     * @return void
     */
    public function getId() {
        return $this->gruppeId;
    }

    /**
     * Hent navn
     *
     * @return String
     */
    public function getNavn() {
        return $this->navn;
    }

    /**
     * Database-spørring
     *
     * @return void
     */
    public static function getLoadQuery()
    {
        return "SELECT * FROM " . OvernattingGruppe::$personerTableName;
    }

    /**
     * Last inn info fra databasen for denne OvernattingGruppen
     *
     * @return Person
     */
    private function _load_from_db()
    {
        $sql = new Query(
            OvernattingGruppe::getLoadQuery() . "
            WHERE `gruppe` = '#gruppeId'",
            [
                'gruppeId' => $this->gruppeId
            ]
        );
        $res = $sql->run();

        while( $row = Query::fetch( $res ) ) {
            $this->_load_from_array($row);   
        }
    }

    /**
     * Last inn info fra et array
     *
     * @param Array $data
     * @return void
     */
    private function _load_from_array(array $res) {
        $this->add(new OvernattingPerson((int) $res['id'], $res['navn'], $res['mobil'], $res['epost'], $res['ankomst'], $res['avreise']));
    }

    /**
     * Get alle personer som er i denne gruppen
     *
     * @return OvernattingPerson[]
     */
    public function getAllePersoner() {
        return $this->getAll();
    }
   
}
