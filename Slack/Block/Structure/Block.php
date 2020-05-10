<?php

namespace UKMNorge\Slack\Block\Structure;

use stdClass;
use UKMNorge\Slack\Payload\Payload;

class Block implements BlockInterface
{
    public $id;
    public $data;

    public function __construct(String $id = null)
    {
        if (!is_null($id)) {
            $this->setId($id);
        }
    }

    /**
     * Set id
     *
     * @param String $id
     * @return self
     */
    public function setId(String $id)
    {
        if (strlen($id) > 255) {
            throw new Exception('Max ID length is 255 characters', 'maxlength_id');
        }
        $this->id = $id;
        return $this;
    }

    /**
     * Get Id
     *
     * @return String
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get type 
     *
     * @return String
     */
    public function getType()
    {
        return static::TYPE;
    }

    /**
     * Start ExportData object with basic data
     * 
     * Most child classes should extend this one
     *
     * @return stdClass
     */
    public function export()
    {
        $data = new stdClass;
        $data->type = Payload::convert($this->getType());

        if (!is_null($this->getId())) {
            $data->block_id = Payload::convert($this->getId());
        }
        return $data;
    }
}
