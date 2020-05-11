<?php

namespace UKMNorge\Some\Log;

use DateTime;
use UKMNorge\Slack\Cache\User\Users;

class Event {
    const TABLE = 'some_log';

    public $objekt_type;
    public $objekt_id;
    public $event_id;
    public $team_id;
    public $user_id;
    public $oppsummering;
    public $data;
    public $timestamp;
    public $eier;

    /**
     * Opprett og lagre et event
     *
     * @param String $objekt_type
     * @param Int $objekt_id
     * @param String $event_id
     * @param String $team_id
     * @param String $user_id
     * @param String $oppsummering
     * @param array $data
     * @return void
     */
    public static function create(String $objekt_type, Int $objekt_id, String $event_id, String $team_id, String $user_id, String $oppsummering, array $data=null)
    {
        $insert = new Insert(static::TABLE);
        $insert->add('objekt_type', $objekt_type);
        $insert->add('objekt_id', $objekt_id);
        $insert->add('event_id', $event_id);
        $insert->add('team_id', $team_id);
        $insert->add('user_id', $user_id);
        $insert->add('oppsummering', $oppsummering);
        $insert->add('data', json_encode($data));

        $res = $insert->run();

        return Log::getByDbId( $res );
    }

    /**
     * Hent ut informasjon om event
     *
     * @param Array $data
     * @return Event
     */
    public function __construct( Array $data )
    {
        $this->timestamp = new DateTime($data['timestamp']);
        $this->objekt_type = $data['objekt_type'];
        $this->objekt_id = $data['objekt_id'];
        $this->event_id = $data['event_id'];
        $this->team_id = $data['team_id'];
        $this->user_id = $data['user_id'];
        $this->oppsummering = $data['oppsummering'];
        $this->data = json_decode($data['data']);
    }

    /**
     * Hent type objekt
     * 
     * @return String classname
     */ 
    public function getObjektType()
    {
        return $this->objekt_type;
    }

    /**
     * Hent objektets id
     * 
     * @return Int
     */ 
    public function getObjektId()
    {
        return $this->objekt_id;
    }

    /**
     * Hent event id
     * 
     * @return String
     */ 
    public function getEventId()
    {
        return $this->event_id;
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
     * Hent kort oppsummering
     * 
     * @return String
     */ 
    public function getOppsummering()
    {
        return $this->oppsummering;
    }

    /**
     * Hent additional data
     * 
     * @return Array
     */ 
    public function getData()
    {
        return $this->data;
    }

    /**
     * Hent tidspunkt for loggføringen
     * 
     * @return DateTime
     */ 
    public function getTimestamp()
    {
        return $this->timestamp;
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

    /**
     * Hent deeplink for eieren
     *
     * @return String html
     */
    public function getEierLink()
    {
        return '<a href="slack://user?team=' .
            $this->getTeamId() . '&id=' . $this->getUserId() . '">' .
            $this->getEier()->getRealName() .
            '</a>';
    }
}