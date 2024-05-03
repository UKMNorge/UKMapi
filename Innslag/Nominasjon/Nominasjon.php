<?php

namespace UKMNorge\Innslag\Nominasjon;

use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Typer\Type;

class Nominasjon extends Placeholder
{
    private $id;
    private $delta_id;
    private $innslag_id;
    private $type;

    private $fra_id;
    private $fra_arrangement;
    private $til_id;
    private $til_arrangement;
    private $sesong;

    private $er_nominert = false;
    private $har_deltakerskjema = false;
    private $har_voksenskjema = false;

    private $voksen;

    private $godkjent = false;
    // Er nominasjon svart av til_arrangement
    private $answered;


    public function __construct(Query $query)
    {
        $data = $query->getArray();
        if (is_array($data)) {
            $this->_loadByRow($data);
        }
    }

    /**
     * Hent databasespørringen som brukes
     *
     * @return String 
     */
    public static function getLoadQuery()
    {
        return "SELECT *
			FROM `ukm_nominasjon`
			JOIN `#table` ON (`#table`.`nominasjon` = `ukm_nominasjon`.`id`)";
    }

    /**
     * Hvilken detalj-tabell lagres nominasjonen i?
     *
     * @param String $innslag_type
     * @return String tabell-navn
     */
    public static function getDetailTable($innslag_type)
    {
        switch ($innslag_type) {
            case 'nettredaksjon':
                return 'ukm_nominasjon_media';
            case 'media':
            case 'arrangor';
            case 'datakulturarrangor';
            case 'konferansier';
                return 'ukm_nominasjon_' . $innslag_type;
            default:
                throw new Exception('NOMINASJON: Kan ikke laste inn nominasjon pga ukjent type ' . $innslag_type, 2);
        }
    }

    /**
     * Last inn en nominasjon fra ID
     *
     * @param Int $innslag_id
     * @param Type $innslag_type
     * @return Nominasjon
     */
    public static function getById(Int $nominasjon_id, Type $innslag_type)
    {
        return new static(
            new Query(
                static::getLoadQuery() . "
                WHERE `ukm_nominasjon`.`id` = '#nominasjon_id'
                LIMIT 1",
                [
                    'table' => static::getDetailTable($innslag_type->getKey()),
                    'nominasjon_id' => $nominasjon_id
                ]
            )
        );
    }

    /**
     * Last inn en nominasjon fra Innslag
     *
     * @param Innslag $innslag_id
     * @param Int $fra_arrangement_id
     * @param Int $til_arrangement_id
     * @return Nominasjon
     */
    public static function getByInnslag(Innslag $innslag, Int $fra_arrangement_id, Int $til_arrangement_id)
    {
        return static::getByInnslagData(
            $innslag->getId(),
            $innslag->getType(),
            $fra_arrangement_id,
            $til_arrangement_id
        );
    }

    /**
     * Last inn en nominasjon fra et innslags data
     *
     * @param Int $innslag_id
     * @param Type $innslag_type
     * @param Int $fra_arrangement_id
     * @param Int $til_arrangement_id
     * @return Nominasjon
     */
    public static function getByInnslagData(Int $innslag_id, Type $innslag_type, Int $fra_arrangement_id, Int $til_arrangement_id)
    {
        return new static(
            new Query(
                static::getLoadQuery() . "
                WHERE `ukm_nominasjon`.`b_id` = '#innslagid'
                AND `ukm_nominasjon`.`arrangement_fra` = '#fra_arrangement'
                AND `ukm_nominasjon`.`arrangement_til` = '#til_arrangement'
                ORDER BY `ukm_nominasjon`.`id` ASC
                LIMIT 1",
                [
                    'table' => static::getDetailTable($innslag_type->getKey()),
                    'innslagid' => $innslag_id,
                    'fra_arrangement' => $fra_arrangement_id,
                    'til_arrangement' => $til_arrangement_id
                ]
            )
        );
    }

    /**
     * Populer objekt-felt fra databaserad
     *
     * @param Array $row
     * @return void
     */
    protected function _loadByRow($row)
    {
        if (!is_array($row)) {
            throw new Exception('NOMINASJON: Kan ikke laste inn nominasjon fra annet enn array', 3);
        }

        $this->id = intval($row['id']);
        $this->innslag_id = intval($row['b_id']);
        $this->sesong = intval($row['season']);
        $this->type = $row['type'];
        $this->er_nominert = $row['nominert'] == 'true';
        $this->fra_id = intval($row['arrangement_fra']);
        $this->til_id = intval($row['arrangement_til']);
        $this->godkjent = $row['godkjent'] == 'true';
        $this->answered = !($row['godkjent'] == null);
        $this->setHarNominasjon(true);

        try {
            $this->setVoksen(new Voksen(intval($row['id'])));
        } catch (Exception $e) {
            $this->setVoksen(new PlaceholderVoksen(null));
        }
    }

    /**
     * Har vi en databaserad for denne nominasjonen?
     *
     * @return Bool
     */
    public function eksisterer()
    {
        return is_numeric($this->getId()) && $this->getId() > 0;
    }

    /**
     * Hent nominasjons-id
     *
     * @return Int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Angi nominasjons-id
     *
     * @param Int $id
     * @return self
     */
    public function setId(Int $id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Hent hvilket innslag dette gjelder
     *
     * @return Int
     */
    public function getInnslagId()
    {
        return $this->innslag_id;
    }

    /**
     * Angi hvilket innslag dette gjelder?
     *
     * @param Int $innslag_id
     * @return self
     */
    public function setInnslagId(Int $innslag_id)
    {
        $this->innslag_id = $innslag_id;
        return $this;
    }

    /**
     * Hvilken innslag(/nominasjon)-type gjelder dette?
     *
     * @return String
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Angi hvilken innslag-type dette gjelder
     *
     * @param String $type
     * @return self
     */
    public function setType(String $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Hent avsenderarrangement
     *
     * @return Arrangement
     */
    public function getFraArrangement() {
        if($this->fra_arrangement == null) {
            $this->fra_arrangement = new Arrangement($this->getFraArrangementId());
        }
        return $this->fra_arrangement; 
    }

    /**
     * Hent avsenderarrangement-id
     *
     * @return Int
     */
    public function getFraArrangementId()
    {
        return $this->fra_id;
    }

    /**
     * Angi avsenderarrangement-id
     *
     * @param Int $arrangement_id
     * @return self
     */
    public function setFraArrangementId(Int $arrangement_id)
    {
        $this->fra_id = $arrangement_id;
        return $this;
    }

    /**
     * Hent mottaker-arrangement.
     *
     * @return Arrangement
     */
    public function getTilArrangement() {
        if($this->til_arrangement == null) {
            $this->til_arrangement = new Arrangement($this->getTilArrangementId());
        }
        return $this->til_arrangement;
    }

    /**
     * Hent mottakerarrangement-id
     *
     * @return Int
     */
    public function getTilArrangementId()
    {
        return $this->til_id;
    }

    /**
     * Angi mottakerarrangement-id
     *
     * @param Int $arrangement_id
     * @return self
     */
    public function setTilArrangementId(Int $arrangement_id)
    {
        $this->til_id = $arrangement_id;
        return $this;
    }

    /**
     * Hvilken sesong gjelder nominasjonen?
     *
     * @return Int
     */
    public function getSesong()
    {
        return $this->sesong;
    }

    /**
     * Angi hvilken sesong nominasjonen gjelder
     *
     * @param Int $sesong
     * @return self
     */
    public function setSesong(Int $sesong)
    {
        $this->sesong = $sesong;
        return $this;
    }

    /**
     * Angi om deltakeren er nominert
     *
     * @param Bool $nominert
     * @return self
     */
    public function setErNominert(Bool $nominert)
    {
        $this->er_nominert = $nominert;
        return $this;
    }

    /**
     * Er deltakeren nominert?
     * 
     * Altså, er det krysset av for at deltakeren skal være nominert?
     *
     * @return Bool
     */
    public function erNominert()
    {
        return $this->er_nominert;
    }

    /**
     * Er det fylt ut deltaker-skjema?
     *
     * @return Bool
     */
    public function harDeltakerskjema()
    {
        if($this->til_id) {
            $arrangement = new Arrangement($this->til_id);
            // Return true på har deltakerskjema fordi når det videresendes til arrangement av type land trengs ikke deltakerskjema
            if($arrangement && $arrangement->getEierType() == 'land') {
                return true;
            }
        }

        return $this->har_deltakerskjema;
    }

    /**
     * Angi om deltaker-skjema er fylt ut
     *
     * @param Bool $bool
     * @return self
     */
    public function setHarDeltakerskjema(Bool $bool)
    {
        $this->har_deltakerskjema = $bool;
        return $this;
    }

    /**
     * Er det fylt ut et voksen-skjema?
     *
     * @return Bool
     */
    public function harVoksenskjema()
    {
        return $this->har_voksenskjema;
    }

    /**
     * Angi om voksen-skjema er fylt ut
     *
     * @param Bool $bool
     * @return self
     */
    public function setHarVoksenskjema(Bool $bool)
    {
        $this->har_voksenskjema = $bool;
        return $this;
    }

    /**
     * Sett voksen-objektet
     *
     * @param PlaceholderVoksen $voksen
     * @return self
     */
    public function setVoksen(PlaceholderVoksen $voksen)
    {
        $this->voksen = $voksen;
        return $this;
    }

    /**
     * Hent voksen-objektet
     *
     * @return Voksern
     */
    public function getVoksen()
    {
        return $this->voksen;
    }

    /**
     * Set godkjent verdi
     * @param Bool $godkjent
     * 
     * @return self
     */
    public function setGodkjent(Bool $godkjent)
    {
        $this->godkjent = $godkjent;
        return $this;
    }

    /**
     * Er nominasjonen godkjent?
     *
     * @return Bool
     */
    public function erGodkjent()
    {
        return $this->godkjent;
    }

    /**
     * Set nominasjonen besvart?
     * 
     * @param Bool $answered
     * 
     * @return self
     */
    public function setAnswered(Bool $answered)
    {
        $this->answered = $answered;
        return $this;
    }

    /**
     * Er nominasjonen besvart?
     *
     * @return Bool
     */
    public function erAnswered()
    {
        return $this->answered;
    }

    	/**
     * Get innslag bassert på innslag id
     * @return Innslag
     */
	public function getInnslag() {
		return Innslag::getById($this->innslag_id);
	}

}
