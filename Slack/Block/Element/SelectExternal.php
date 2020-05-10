<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Composition\Text;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Block\Structure\Select as SelectStructure;
use UKMNorge\Slack\Payload\Payload;

class SelectExternal extends SelectStructure
{
    const TYPE = 'external_select';

    public $min_query_length;
    public $initial_option;

    /**
     * Create a new multiselect list
     *
     * @param String $action_id
     * @param Text $placeholder
     */
    public function __construct(String $action_id, Text $placeholder)
    {
        $this->setActionId($action_id);
        $this->setPlaceholder($placeholder);
    }

    /**
     * Get initial option (SINGLE SELECT ONLY)
     *
     * @return Options
     */
    public function getInitialOption()
    {
        static::requireSingleSelect();
        return $this->initial_option;
    }

    /**
     * Set initial option (SINGLE SELECT ONLY)
     *
     * @param Option $option
     * @return self
     */
    public function setInitialOption( Option $option ) {
        static::requireSingleSelect();
        $this->initial_option = $option;
        return $this;
    }


    /**
     * Set minimum of characters before slack queries for options
     *
     * @param Int $max
     * @return self
     */
    public function setMinQueryLength(Int $min)
    {
        if ($min < 1) {
            throw new Exception(
                'Minimum query length must be a positive number',
                'invalid_min_query_length'
            );
        }
        $this->min_query_length = $min;
    }

    /**
     * Get minimum of characters before slack queries for options
     *
     * @return Int
     */
    public function getMinQueryLength()
    {
        return $this->min_query_length;
    }

    /**
     * Get export data
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export(); // type + action_id (if not null) + confirm (if not null)

        if (!is_null($this->getMinQueryLength())) {
            $data->min_query_length = Payload::convert($this->getMinQueryLength());
        }

        // Initial option (single mode)
        if( static::isSingleSelect() && !is_null($this->getInitialOption())) {
            $data->initial_option = Payload::convert($this->getInitialOption());
        }
        
        return $data;
    }
}
