<?php

namespace UKMNorge\UKMFestivalen\Overnatting;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class Samling extends Collection
{
    private $tableName = 'ukm_festival_overnatting_gruppe';

    /**
     * Last inn alle OvernattingGruppe
     *
     * @return void
     */
    public function _load()
    {
        $sql = new Query(
            "SELECT * FROM " . $this->tableName
        );
        $res = $sql->run();
        if ($res) {
            while ($row = Query::fetch($res)) {
                $this->add(new OvernattingGruppe($row['id'], $row['navn']));
            }
        }
    }

    /**
     * Hent gitt OvernattingGruppe fra ID
     *
     * @param Int $id
     * @throws Exception
     * @return OvernattingGruppe
     */
    public static function getById(Int $id) {
        $sql = new Query(
            OvernattingGruppe::getLoadQuery() . "
						WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );
        $res = $sql->getArray();
        if ($res) {
            return new OvernattingGruppe($res);
        }
        throw new Exception(
            'Kunne ikke hente mediefil',
            185000
        );
    }
}
