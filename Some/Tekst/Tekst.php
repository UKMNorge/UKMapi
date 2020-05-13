<?php

namespace UKMNorge\Some\Tekst;

use UKMNorge\Slack\Cache\User\Users;
use UKMNorge\Some\Kanaler\Kanaler;

class Tekst
{
    const TABLE = 'some_status_tekst';

    public $id;
    public $objekt_id;
    public $objekt_type;
    public $kanal_id;
    public $team_id;
    public $user_id;
    public $tekst;
    public $notater;

    public $kanal;
    public $eier;

    public function __construct(array $data)
    {
        $this->id = intval($data['id']);
        $this->objekt_type = $data['objekt_type'];
        $this->objekt_id = intval($data['objekt_id']);
        $this->kanal_id = $data['kanal_id'];
        $this->team_id = $data['team_id'];
        $this->user_id = $data['user_id'];
        $this->tekst = $data['tekst'];
        $this->notater = $data['notater'];
    }

    /**
     * Array-representasjon av objektet
     *
     * @return Array
     */
    public function __toArray()
    {
        return get_object_vars($this);
    }

    /**
     * Hent tekstens database-id
     *
     * @return Int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sett ansvarlig brukers team id
     *
     * @param String $team_id
     * @return self
     */
    public function setTeamId(String $team_id)
    {
        $this->team_id = $team_id;
        return $this;
    }

    /**
     * Hent team eventet skjedde i
     * 
     * @return String
     */
    public function getTeamId()
    {
        return $this->team_id;
    }

    /**
     * Oppdater ansvarlig bruker-id
     *
     * @param String $user_id
     * @return self
     */
    public function setUserId(String $user_id)
    {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * Hent slack bruker-id
     * 
     * @return String
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Hent objekt-id på parent-objektet
     */
    public function getObjektId()
    {
        return $this->objekt_id;
    }

    /**
     * Hent parent-objektets type
     *
     * @return String (ide|status)
     */
    public function getObjektType()
    {
        return $this->objekt_type;
    }

    /**
     * Hent kanal-id
     * 
     * @return String
     */
    public function getKanalId()
    {
        return $this->kanal_id;
    }

    /**
     * Hent kanalen
     *
     * @return Kanal
     */
    public function getKanal()
    {
        if (is_null($this->kanal)) {
            $this->kanal = Kanaler::getById($this->getKanalId());
        }
        return $this->kanal;
    }

    /**
     * Oppdater teksten
     *
     * @param String $tekst
     * @return self
     */
    public function setTekst(String $tekst)
    {
        $this->tekst = $tekst;
        return $this;
    }

    /**
     * Hent selve teksten
     * 
     * @param String
     */
    public function getTekst()
    {
        return $this->tekst;
    }

    /**
     * Oppdater notater
     *
     * @param String $notater
     * @return self
     */
    public function setNotater(String $notater) {
        $this->notater = $notater;
        return $this;
    }

    /**
     * Hent notater
     *
     * @return String
     */
    public function getNotater() {
        return $this->notater;
    }

    /**
     * Hent eier-objektet
     *
     * @return User
     */
    public function getEier()
    {
        if (is_null($this->eier)) {
            $this->eier = Users::getBySlackId($this->getUserId());
        }
        return $this->eier;
    }
}
