<?php

namespace UKMNorge\Slack\Block\Structure;

use stdClass;
use UKMNorge\Slack\Block\Element\ElementInterface;
use UKMNorge\Slack\Payload\Payload;

class Element implements ElementInterface
{
    public $action_id;
    public $confirm;

    /**
     * Get type of element
     *
     * @return String
     */
    public function getType()
    {
        return static::TYPE;
    }

    /**
     * Set the action id
     *
     * @param String $action_id
     * @return self
     */
    public function setActionId(String $action_id)
    {
        if (strlen($action_id) > 255) {
            throw new Exception('Action id must be less than 255 characters long. Given ' . strlen($action_id), 'maxlength_action_id');
        }
        $this->action_id = $action_id;
        return $this;
    }

    /**
     * Get the action Id
     *
     * @return String
     */
    public function getActionId()
    {
        return $this->action_id;
    }

    /**
     * Set confirm object
     *
     * @param Confirm $confirm
     * @return self
     */
    public function setConfirm(Confirm $confirm)
    {
        $this->confirm = $confirm;
        return $this;
    }

    /**
     * Get confirm object
     *
     * @return Confirm
     */
    public function getConfirm()
    {
        return $this->confirm;
    }

    /**
     * Start ExportData object with basic data
     * 
     * Most child classes should extend this one
     * 
     * // type + action_id (if not null) + confirm (if not null)
     *
     * @return ExportData
     */
    public function export()
    {
        $data = new stdClass();
        $data->type = Payload::convert($this->getType());

        // Action id is required
        if (is_null($this->getActionId()) && static::REQUIRE_ACTION_ID) {
            throw new Exception('Missing required action_id', 'missing_action_id');
        }
        // Action id optional but included
        elseif (!is_null($this->getActionId())) {
            $data->action_id = Payload::convert($this->getActionId());
        }

        if (!is_null($this->getConfirm())) {
            $data->confirm = Payload::convert($this->getConfirm());
        }
        return $data;
    }

    /**
     * Warn about unsupported function
     * 
     * Used by child classes
     *
     * @param String $function
     * @param String $id
     * @throws Exception
     */
    public static function unsupported(String $function, String $id)
    {
        throw new Exception(static::class . ' does not support ' . $function, 'unsupported_' . $id);
    }
}
