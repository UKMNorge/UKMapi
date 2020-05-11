<?php

namespace UKMNorge\Some\Forslag;

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
    }

    /**
     * Array-representasjon av objektet
     *
     * @return Array
     */
    public function __toArray() {
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
     * Hent team eventet skjedde i
     * 
     * @return String
     */
    public function getTeamId()
    {
        return $this->team_id;
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
    public function getOjektId()
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
     * Hent selve teksten
     * 
     * @param String
     */
    public function getTekst()
    {
        return $this->tekst;
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
