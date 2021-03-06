<?php

namespace UKMNorge\Some\Tekst;

use Exception;
use UKMNorge\Slack\Cache\User\User;
use UKMNorge\Slack\Cache\User\Users;
use UKMNorge\Some\Kanaler\Kanaler;

class Tekst
{
    const TABLE = 'some_status_tekst';
    const STATUS_ENUM = ['ferdig','kladd'];

    public $id;
    public $objekt_id;
    public $objekt_type;
    public $kanal_id;
    public $eier;
    public $tekst;
    public $notater;
    public $status;

    public $kanal;

    public function __construct(array $data)
    {
        $this->id = intval($data['id']);
        $this->objekt_type = $data['objekt_type'];
        $this->objekt_id = intval($data['objekt_id']);
        $this->kanal_id = $data['kanal_id'];
        $this->eier = Users::getBySlackId($data['team_id'], $data['user_id']);
        $this->tekst = $data['tekst'];
        $this->notater = $data['notater'];
        $this->status = $data['status'];
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
    public function setNotater(String $notater)
    {
        $this->notater = $notater;
        return $this;
    }

    /**
     * Hent notater
     *
     * @return String
     */
    public function getNotater()
    {
        return $this->notater;
    }

    /**
     * Hent eier-objektet
     *
     * @return User
     */
    public function getEier()
    {
        return $this->eier;
    }

    /**
     * Sett eier-objekt
     *
     * @param User $user
     * @return self
     */
    public function setEier(User $user)
    {
        $this->eier = $user;
        return $this;
    }

    /**
     * Er teksten en kladd?
     *
     * @return Bool
     */
    public function erKladd()
    {
        return $this->status == 'kladd';
    }

    /**
     * Er teksten ferdig?
     *
     * @return Bool
     */
    public function erFerdig()
    {
        return $this->status == 'ferdig';
    }

    /**
     * Oppdater status for teksten
     *
     * @param String <kladd|ferdig>
     * @return self
     */
    public function setStatus( String $status ) {
        if( !in_array($status, static::STATUS_ENUM)) {
            throw new Exception('Ukjent status for tekst: '. $status);
        }
        $this->status = $status;
        return $this;
    }

    /**
     * Hent status som streng
     *
     * @return String
     */
    public function getStatus() {
        return $this->status;
    }
}
