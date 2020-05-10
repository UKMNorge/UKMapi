<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Composition\Text;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

class MultiSelectExternal extends SelectExternal
{
    const TYPE = 'multi_external_select';
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
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export(); // type + action_id (if not null) + confirm (if not null)

        // Initial options (multi mode)
        if ($this->getInitialOptions()->getLength() > 0) {
            $data->initial_options = Payload::convert($this->getOptions());
        }

        return $data;
    }
}
