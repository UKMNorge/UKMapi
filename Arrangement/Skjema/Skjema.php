<?php

namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Eier;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Samtykkeskjema\SkjemaSuper;
use Exception;
use SporsmalColl;

require_once('UKM/Autoloader.php');

class Skjema extends SkjemaSuper {

    protected string $id;
    private $arrangement_id;
    private $eier;
    private $type;
    private $sporsmal;
    private $overskrifter;
    private $gruppert;
    private $respondenter;
    protected string $navn;
    
    /**
     * 
     * Hent respondent by user id og sjekk om alle svar er besvart
     * 
     * Returnerer true KUN hvis alle svar er besvart, ellers false
     * 
     * @param Int $userId
     * @return bool
     * @override SkjemaSuper
     */
    public function isAnswered($userId) : bool {
        $respondenter = $this->getRespondenter()->getAll();
        foreach($respondenter as $respondent) {
            if($respondent->getId() == $userId) {
                foreach($respondent->getSvar()->getAll() as $svar) {
                    // If any answer is not answered, return false
                    if(!$svar->isAnswered()) {
                        return false;
                    }
                }
                // Respondent found and all answers are answered
                return true;
            }
        }
        // No respondent found
        return false;
    }

    /**
     * Er skjemaet besvart, er alle spørsmålene besvart og skjemaet godkjent?
     * 
     * @return bool
     * @override SkjemaSuper
     */
    public function isGodkjent($userId) : bool {
        // return $this->getRespondenter()->harGodkjent();
        return false;
    }

    /**
     * Hent alle oppgave-skjemaer for et arrangement
     * 
     * @param Int $pl_id
     * @return Skjema[]
     */
    public static function getOppgaveSkjemaer(Int $pl_id)
    {
        $query = new Query(
            "SELECT `id`
            FROM `ukm_videresending_skjema`
            WHERE `pl_id` = '#arrangement'
            AND `type` = 'oppgave'",
            [
                'arrangement' => $pl_id
            ]
        );

        $skjemaer = [];
        $res = $query->run();
        while ($row = Query::fetch($res)) {
            $skjemaer[] = static::load(
                new Query(
                    "SELECT *
                    FROM `ukm_videresending_skjema`
                    WHERE `id` = '#id'",
                    [
                        'id' => $row['id']
                    ]
                ),
                'oppgave'
            );
        }
        return $skjemaer;
    }

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

    public static function getById(Int $id) : Skjema {
        return static::load(
            new Query(
                "SELECT *
                FROM `ukm_videresending_skjema`
                WHERE `id` = '#id'",
                [
                    'id' => $id
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
     * @return string
     */
    public function getNavn(): string {
        return $this->navn;
    }

    /**
     * @param string $navn
     */
    public function setNavn(string $navn): void {
        $this->navn = $navn;
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
    public function __construct(Int $id, String $type, Int $pl_id, String $eier_type, Int $eier_id, String $navn = "")
    {
        $this->id = $id;
        $this->arrangement_id = $pl_id;
        $this->eier = new Eier($eier_type, $eier_id);
        $this->type = $type;
        $this->navn = $navn;
    }

    public function getEier() : Eier {
        return $this->eier;
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
        $navn       = isset($skjema_data['name'])       ? $skjema_data['name'] : "";

        return new static(
            $id,
            $type,
            $pl_id,
            $eier_type,
            $eier_id,
            $navn
        );
    }
}
