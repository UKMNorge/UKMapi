<?php

namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Database\SQL\Query;

class Overskrifter extends SporsmalSamling
{   
    /**
     * Last inn spørsmål fra databasen
     * 
     * @return void
     */
    public function load()
    {
        $this->sporsmal = [];
        $select = new Query(
            "SELECT * 
            FROM `ukm_videresending_skjema_sporsmal`
            WHERE `skjema` = '#skjema'
            AND `type` = 'overskrift'
            ORDER BY `rekkefolge` ASC",
            [
                'skjema' => $this->getSkjemaId()
            ]
        );
        $res = $select->run();
        while ($db_row = Query::fetch($res)) {
            $this->sporsmal[] = Sporsmal::createFromDatabase($db_row);
        }
    }
}
