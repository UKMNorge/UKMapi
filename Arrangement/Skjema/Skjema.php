<?php

namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Eier;
use UKMNorge\Database\SQL\Query;
use Exception;
use SporsmalColl;

require_once('UKM/Autoloader.php');

class Skjema
{

    private $id;
    private $arrangement_id;
    private $eier;
    private $type;
    private $sporsmal;
    private $overskrifter;
    private $gruppert;
    private $respondenter;

    /**
     * Hent skjema for arrangement
     *
     * @param Int $pl_id
     * @return Skjema $skjema
     */
    public static function getArrangementSkjema(Int $pl_id)
    {
        return static::load(
            new Query(
                "SELECT `id`
            FROM `ukm_videresending_skjema`
            WHERE `pl_id` = '#arrangement'
            AND `type` = 'arrangement'",
                [
                    'arrangement' => $pl_id
                ]
            ),
            'arrangement'
        );
    }

    /**
     * Hent skjema for deltakere (person)
     * 
     * @param Int $pl_id
     * @return Skjema $skjema
     */
    public static function getDeltakerSkjema(Int $pl_id)
    {
        return static::load(
            new Query(
                "SELECT `id`
                FROM `ukm_videresending_skjema`
                WHERE `pl_id` = '#arrangement'
                AND `type` = 'person'",
                [
                    'arrangement' => $pl_id
                ]
            ),
            'person'
        );
    }

    /**
     * Hent skjema-ID
     * 
     * @return Int $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent hvilken type skjema dette er
     * 
     * @return String arrangement|person
     */
    public function getType()
    {
        return $this->type;
    }
    /**
     * Hent arrangement-ID (pl_id)
     *
     * @return Int $pl_id
     */
    public function getArrangementId()
    {
        return $this->arrangement_id;
    }

    /**
     * Henter alle spørsmål, eller ett gitt spørsmål
     * 
     * @param Int Spørsmål-ID, default null
     * @throws Exception
     * @return SporsmalSamling
     */
    public function getSporsmal(Int $sporsmal_id = null)
    {
        if (is_null($this->sporsmal)) {
            $this->sporsmal = new SporsmalSamling($this->getId());
        }
        return $this->sporsmal;
    }

    /**
     * Hvor mange overskrifter har skjemaet 
     *
     * @return Int
     */
    public function getAntallOverskrifter()
    {
        return $this->getOverskrifter()->getAntall();
    }

    /**
     * Hent alle overskrifter
     *
     * @return Overskrifter
     */
    public function getOverskrifter()
    {
        if (is_null($this->overskrifter)) {
            $this->overskrifter = new Overskrifter($this->getId());
        }
        return $this->overskrifter;
    }

    /**
     * Hent alle spørsmål, men gruppert per overskrift
     * 
     * @return Array<Gruppe>
     */
    public function getSporsmalPerOverskrift() {
        if (is_null($this->gruppert)) {
            $this->gruppert = [];
            $count = 0;
            $current_index = 0;
            foreach ($this->getSporsmal()->getAll() as $sporsmal) {
                if ($count == 0 && $sporsmal->getType() != 'overskrift') {
                    $this->gruppert[] = Gruppe::createEmpty();
                    $current_index = sizeof($this->gruppert) - 1;
                }

                switch ($sporsmal->getType()) {
                    case 'overskrift':
                        $this->gruppert[] = Gruppe::createFromSporsmal($sporsmal);
                        $current_index = sizeof($this->gruppert) - 1;
                        break;
                    default:
                        $this->gruppert[$current_index]->add($sporsmal);
                }

                $count++;
            }
        }
        return $this->gruppert;
    }

    /**
     * Hent alle som har respondert på skjemaet
     * 
     * @return Respondenter
     */
    public function getRespondenter()
    {
        if (is_null($this->respondenter)) {
            $this->respondenter = new Respondenter($this);
        }
        return $this->respondenter;
    }


    /**
     * Opprett et objekt
     * 
     * @see getArrangementSkjema or getDeltakerskjema
     * @return self
     */
    public function __construct(Int $id, String $type, Int $pl_id, String $eier_type, Int $eier_id)
    {
        $this->id = $id;
        $this->arrangement_id = $pl_id;
        $this->eier = new Eier($eier_type, $eier_id);
        $this->type = $type;
    }

    /**
     * Last inn skjema fra Query
     * 
     * @param Query $query
     * @param String $eier_type
     * @return Skjema $skjema
     */
    private static function load(Query $query, String $eier_type)
    {
        $skjema_data = $query->getArray();
        if (!$skjema_data || is_null($skjema_data)) {
            throw new Exception(
                'Finner ikke skjema for '. $eier_type,
                151002
            );
        }

        $id         = isset($skjema_data['id'])         ? intval($skjema_data['id']) : 0;
        $type       = isset($skjema_data['type'])       ? $skjema_data['type'] : $eier_type;
        $pl_id      = isset($skjema_data['pl_id'])      ? intval($skjema_data['pl_id']) : 0;
        $eier_type  = isset($skjema_data['eier_type'])  ? $skjema_data['eier_type'] : $eier_type;
        $eier_id    = isset($skjema_data['eier_id'])    ? intval($skjema_data['eier_id']) : 0;


        return new static(
            $id,
            $type,
            $pl_id,
            $eier_type,
            $eier_id
        );
    }
}
