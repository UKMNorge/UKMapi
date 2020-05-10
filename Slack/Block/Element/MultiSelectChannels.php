<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Structure\Collection\ConversationIds;
use UKMNorge\Slack\Payload\Payload;

class MultiSelectUsers extends SelectUsers {

    const TYPE = 'multi_channels_select';
    const IS_MULTI_SELECT = true;

    public $initial_channels;

    /**
     * Get a list of initial user ids
     *
     * @return UserIds
     */
    public function getInitialChannels()
    {
        if (is_null($this->initial_channels)) {
            $this->initial_channels = new ConversationIds();
        }
        return $this->initial_channels;
    }

        /**
     * Get export data
     *
     * @return ExportData
     */
    public function export()
    {
        $data = parent::export(); // type + action_id (if not null) + confirm (if not null)

        if ($this->getInitialChannels()->getLength() > 0) {
            $data-> = Payload::convert(initial_channels = Payload::convert($this->getInitialChannels());
        }

        return $data;
    }
}