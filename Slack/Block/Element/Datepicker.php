<?php

namespace UKMNorge\Slack\Block\Element;

use UKMNorge\Slack\Block\Structure\ElementWithPlaceholder;
use UKMNorge\Slack\Payload\Payload;

class Datepicker extends ElementWithPlaceholder
{
    const TYPE = 'datepicker';
    const REQUIRE_ACTION_ID = true;

    const MAX_PLACEHOLDER_LENGTH = 150;

    public $initial_date;

    public function __construct(String $action_id)
    {
        $this->setActionId($action_id);
    }

    /**
     * Set initial date (YYYY-MM-DD)
     *
     * @param String $initial_date
     * @return self
     */
    public function setInitialDate(String $initial_date)
    {
        // TODO VALIDATE FORMAT
        $this->initial_date = $initial_date;
        return $this;
    }

    /**
     * Get initial date
     *
     * @return String
     */
    public function getInitialDate()
    {
        return $this->initial_date;
    }

    /**
     * Get export data
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export(); // type + action_id (if not null) + confirm (if not null)

        // Placeholder
        if (!is_null($this->getPlaceholder())) {
            $data->placeholder = Payload::convert($this->getPlaceholder());
        }

        // Initial date
        if (!is_null($this->getInitialDate())) {
            $data->initial_date = Payload::convert($this->getInitialDate());
        }

        return $data;
    }
}
