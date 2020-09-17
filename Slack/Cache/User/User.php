<?php

namespace UKMNorge\Slack\Cache\User;

use stdClass;
use DateTime;

class User
{
    const TABLE = "slack_user";

    public $id;
    public $team_id;
    public $slack_id;
    public $name;
    public $real_name;
    public $data;
    public $updated;
    public $loaded = false;
    public $do_lazyload = true;
    public $active = true;

    public function __construct(array $data, $lazyload = false)
    {
        if (isset($data['id'])) {
            $this->id = $data['id'];
        }

        $this->team_id = $data['team_id'];
        $this->slack_id = $data['slack_id'];

        if (!$lazyload) {
            $this->loaded = true;
            $this->name = $data['name'];
            $this->real_name = $data['real_name'];
            $this->data = $data['data'];
            $this->active = $data['active'] == 'true';
            $this->updated = new DateTime($data['timestamp']);
        }
    }

    /**
     * Deactivate lazyloading
     *
     * @return Bool true
     */
    public function deactivateLoad()
    {
        $this->do_lazyload = false;
        return true;
    }

    /**
     * Mark user as deactivated
     *
     * @return self
     */
    public function deactivate() {
        $this->active = false;
        return $this;
    }

    /**
     * Mark user as active
     *
     * @return self
     */
    public function activate() {
        $this->active = true;
    }

    /**
     * Is user active
     *
     * @return boolean
     */
    public function isActive() {
        return $this->active;
    }

    /**
     * Get internal db id
     *
     * @return Int
     */
    public function getId()
    {
        $this->_lazyload();
        return $this->id;
    }

    /**
     * Get team id
     *
     * @return String
     */
    public function getTeamId()
    {
        return $this->team_id;
    }

    /**
     * Get slack user id
     *
     * @return String
     */
    public function getSlackId()
    {
        return $this->slack_id;
    }

    /**
     * Get name (handlebar)
     *
     * @return String
     */
    public function getName()
    {
        $this->_lazyload();
        return $this->name;
    }

    /**
     * Set name (handlebar)
     *
     * @param String $name
     * @return self
     */
    public function setName(String $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the real name (display name)
     *
     * @return String
     */
    public function getRealName()
    {
        $this->_lazyload();
        return $this->real_name;
    }

    /**
     * Set the real name (display name)
     *
     * @param String $real_name
     * @return self
     */
    public function setRealName(String $real_name)
    {
        $this->real_name = $real_name;
        return $this;
    }


    /**
     * Get additional data
     * 
     * All data given from slack
     *
     * @return stdClass
     */
    public function getAdditionalData()
    {
        return $this->data;
    }

    /**
     * Set additional data object (no merge)
     *
     * @param stdClass $data
     * @return self
     */
    public function setAdditionalData(stdClass $data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get last time updated in cache
     *
     * @return DateTime
     */
    public function getUpdated()
    {
        $this->_lazyload();
        return $this->updated;
    }

    /**
     * Fetch user deeplink as html
     *
     * @return String html
     */
    public function getLink()
    {
        return '<a href="' . $this->getSlackLink() . '">' .
            $this->getNameOrHandlebar() .
            '</a>';
    }

    public function getSlackLink()
    {
        return 'slack://user?team=' .
            $this->getTeamId() . '&id=' . $this->getSlackId();
    }

    /**
     * Return display name, fallback to handlebar
     *
     * @return String 
     */
    public function getNameOrHandlebar()
    {
        if (!empty($this->getRealName())) {
            return $this->getRealName();
        }
        return $this->getName();
    }

    /**
     * Load user infos from database
     *
     * @return Bool did actually load
     */
    private function _lazyload()
    {
        if (!$this->do_lazyload) {
            return false;
        }

        $data = Users::getBySlackId($this->getTeamId(), $this->getSlackId());
        foreach (get_object_vars($data) as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }
}
