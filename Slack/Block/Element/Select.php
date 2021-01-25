<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Composition\Option;
use UKMNorge\Slack\Block\Composition\Text;
use UKMNorge\Slack\Block\Structure\Collection\Options;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Block\Structure\Select as SelectStructure;
use UKMNorge\Slack\Block\Structure\Collection\OptionGroups;
use UKMNorge\Slack\Payload\Payload;

class Select extends SelectStructure
{
    const TYPE = 'static_select';

    const MAX_OPTIONS = 100;
    const MAX_OPTION_GROUPS = 100;

    public $options;
    public $option_groups;
    public $initial_option;

    /**
     * Create a new multiselect list
     *
     * @param String $action_id
     * @param Text $placeholder
     * @param array $options
     */
    public function __construct(String $action_id, Text $placeholder, array $options=[])
    {
        $this->setActionId($action_id);
        $this->getOptions()->set($options);
        $this->setPlaceholder($placeholder);
    }

    /**
     * Get collection of options
     *
     * @return Options
     */
    public function getOptions()
    {
        if (is_null($this->options)) {
            $this->options = new Options(static::MAX_OPTIONS);
        }
        return $this->options;
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
     * Get collection of option groups
     *
     * @return OptionGroups
     */
    public function getOptionGroups()
    {
        if (is_null($this->option_groups)) {
            $this->option_groups = new OptionGroups(static::MAX_OPTION_GROUPS);
        }
        return $this->option_groups;
    }

 
    /**
     * Get export data
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export(); // type + placeholder + action_id (if not null) + confirm (if not null) + max_selected_items (if not null)

        // Options
        if ($this->getOptions()->getLength() == 0) {
            throw new Exception('Checkboxes require at least one option', 'missing_options');
        }
        $data->options = Payload::convert($this->getOptions());

        // Option groups
        if( $this->getOptionGroups()->getLength() > 0 ) {
            $data->option_groups = Payload::convert($this->getOptionGroups());
        }

        // Initial option (single mode)
        if( static::isSingleSelect() && !is_null($this->getInitialOption())) {
            $data->initial_option = Payload::convert($this->getInitialOption());
        }

        return $data;
    }
}