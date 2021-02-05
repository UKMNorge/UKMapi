<?php

namespace UKMNorge\Arrangement\Skjema;

use Exception;
use UKMNorge\Database\SQL\Query;

class Respondenter
{

    private $skjema_id;
    private $skjema_type;
    private $respondenter;

    public function __construct(Skjema $skjema)
    {
        $this->skjema_id = $skjema->getId();
        $this->skjema_type = $skjema->getType();
    }


    /**
     * Hent alle respondenter
     * 
     * @return Array<Respondent>
     */
    public function getAll()
    {
        if (is_null($this->respondenter)) {
            $this->load();
        }
        return $this->respondenter;
    }

    /**
     * Hent antall respondenter
     * 
     * @return Int
     */
    public function getAntall()
    {
        return sizeof($this->respondenter);
    }

    /**
     * Har noen svart på skjemaet?
     * 
     * @return bool
     */
    public function harRespondenter()
    {
        return $this->getAntall() > 0;
    }

    /**
     * Har en gitt respondent avgitt noen svar?
     * 
     * @param Int $id
     * @return bool
     */
    public function harRespondert(Int $id ) {
        try {
            $this->get($id);
            return true;
        } catch( Exception $e) {
            if( $e->getCode() == 163003) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * Hent en gitt respondent
     * 
     * @return Respondent
     */
    public function get(Int $id)
    {
        if (!isset($this->getAll()[$id])) {
            throw new Exception(
                'Beklager, finner ikke respondent ' . $id,
                163003
            );
        }
        return $this->getAll()[$id];
    }

    /**
     * Laster inn alle respondenter
     */
    private function load()
    {
        $felt = $this->getSkjemaType() == 'arrangement' ? 'pl_fra' : 'p_fra';
        $query = new Query(
            "SELECT *
            FROM `ukm_videresending_skjema_svar`
            WHERE `#felt` IS NOT NULL
            AND `skjema` = '#skjema_id'
            GROUP BY `#felt`
            ORDER BY `id` ASC",
            [
                'felt' => $felt,
                'skjema_id' => $this->getSkjemaId()
            ]
        );
    
        $res = $query->getResults();

        while ($row = Query::fetch($res)) {
            $id = $row[$felt];
            $respondent = new Respondent($id, $this->getSkjemaType(), $this->getSkjemaId());
            $this->respondenter[$respondent->getNavn() .'-'. $id] = $respondent;
        }
    }

    /**
     * Hent skjemaets id
     * 
     * @return Int
     */
    public function getSkjemaId()
    {
        return $this->skjema_id;
    }

    /**
     * Hent skjemaets type
     * 
     * @return String <arrangement|person>
     */
    public function getSkjemaType()
    {
        return $this->skjema_type;
    }
}
