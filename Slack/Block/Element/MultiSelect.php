<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Structure\Collection\Options;
use UKMNorge\Slack\Payload\Payload;

class MultiSelect extends Select
{
    const TYPE = 'multi_static_select';
    const IS_MULTI_SELECT = true;

    public $initial_options;

    /**
     * Get collection of initial options
     *
     * @return Options
     */
    public function getInitialOptions()
    {
        static::requireMultiSelect();
        if (is_null($this->initial_options)) {
            $this->initial_options = new Options(static::MAX_OPTIONS);
        }
        return $this->initial_options;
    }
 
    /**
     * Get export data
     *
     * @return ExportData
     */
    public function export()
    {
        $data = parent::export(); // type + placeholder + action_id (if not null) + confirm (if not null) + max_selected_items (if not null)

        // Initial options (multi mode)
        if ( static::isMultiSelect() && $this->getInitialOptions()->getLength() > 0) {
            $data->initial_options = Payload::convert($this->getInitialOptions());
            
        }

        return $data;
    }
}