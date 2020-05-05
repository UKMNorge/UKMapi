<?php

namespace UKMNorge\Slack\Channel;

use stdClass;
use DateTime;

class Channel
{
    const TABLE = "slack_channel";

    public $id;
    public $team_id;
    public $slack_id;
    public $name;
    public $description;
    public $data;
    public $updated;

    public function __construct(array $data)
    {
        if (isset($data['id'])) {
            $this->id = $data['id'];
        }

        $this->team_id = $data['team_id'];
        $this->slack_id = $data['slack_id'];
        $this->name = $data['name'];
        $this->description = $data['description'];
        $this->data = $data['data'];
        $this->updated = new DateTime($data['timestamp']);
    }

    /**
     * Get internal db id
     *
     * @return Int
     */
    public function getId() {
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
     * Get the real channel description
     *
     * @return String
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the description
     *
     * @param String $description
     * @return self
     */
    public function setDescription(String $description)
    {
        $this->description = $description;
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

    public function getUpdated() {
        return $this->updated;
    }
}
