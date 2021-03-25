<?php

namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Database\SQL\Query;

class SporsmalSamling
{

    private $skjema_id;
    private $sporsmal;

    public function __construct(Int $skjema_id)
    {
        $this->skjema_id = $skjema_id;
    }

    /**
     * Hent alle spørsmål i skjemaet
     * 
     * @return Array<Sporsmal>
     */
    public function getAll()
    {

        if (is_null($this->sporsmal)) {
            $this->load();
        }
        
        return $this->sporsmal;
    }

    /**
     * Hent antall spørsmål i skjemaet
     * 
     * @return Int
     */
    public function getAntall() {
        return sizeof($this->getAll());
    }

    /**
     * Hent hvilket skjema disse spørsmålene er for
     * 
     * @return Int
     */
    public function getSkjemaId() {
        return $this->skjema_id;
    }
    
    /**
     * Last inn spørsmål fra databasen
     * 
     * @return void
     */
    private function load()
    {
        $this->sporsmal = [];
        $select = new Query(
            "SELECT * 
            FROM `ukm_videresending_skjema_sporsmal`
            WHERE `skjema` = '#skjema'
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
