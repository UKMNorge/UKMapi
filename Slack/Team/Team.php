<?php

namespace UKMNorge\Slack\Team;

use DateTime;
use UKMNorge\Slack\Channel\Channels;
use UKMNorge\Slack\User\Users;

class Team {

    public $id;
    private $team_id;
    private $team_name;
    private $access_token;
    private $timestamp;
    private $data;

    private $users;
    private $channels;

    public function __construct( Array $data ) {

        $this->id = $data['id'];
        $this->team_id = $data['team_id'];
        $this->team_name = $data['team_name'];
        $this->access_token = $data['access_token'];
        $this->timestamp = new DateTime( $data['timestamp'] );
        $this->data = $data['data'];
    }

    /**
     * Get internal ID
     *
     * @return Int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set internal Id
     *
     * @param Int $id
     * @return self
     */
    public function setId( Int $id ) {
        $this->id = $id;
        return $this;
    }

    /**
     * Get slack team id
     *
     * @return String
     */
    public function getTeamId() {
        return $this->team_id;
    }

    /**
     * Set slack team id
     *
     * @param String $team_id
     * @return self
     */
    public function setTeamId(String $team_id) {
        $this->team_id = $team_id;
        return $this;
    }

    /**
     * Get team name
     *
     * @return String
     */
    public function getTeamName() {
        return $this->team_name;
    }

    /**
     * Set team name
     *
     * @param String $team_name
     * @return self
     */
    public function setTeamName(String $team_name) {
        $this->team_name = $team_name;
        return $this;
    }

    /**
     * Get access token
     *
     * @return String
     */
    public function getAccessToken() {
        return $this->access_token;
    }

    /**
     * Set access token
     *
     * @param String $access_token
     * @return self
     */
    public function setAccessToken(String $access_token) {
        $this->access_token = $access_token;
        return $this;
    }

    /**
     * Get timestamp last modified
     *
     * @return DateTime
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Get additional data
     *
     * @return Array ?
     */
    public function getAdditionalData() {
        return $this->data;
    }

    /**
     * Set additional data array (overwrite!)
     *
     * @param Array $data
     * @return self
     */
    public function setAdditionalData($data) {
        $this->data = $data;
        return $this;
    }

    /**
     * Get user collection
     *
     * @return Users
     */
    public function getUsers() {
        if( is_null($this->users) ) {
            $this->users = new Users($this->getTeamId());
        }
        return $this->users;
    }

    public function getChannels() {
        if( is_null($this->channels)) {
            $this->channels = new Channels($this->getTeamId());
        }
        return $this->channels;
    }
}