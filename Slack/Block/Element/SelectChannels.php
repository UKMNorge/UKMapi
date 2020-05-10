<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Composition\Filter;
use UKMNorge\Slack\Block\Structure\Collection\ConversationIds;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Block\Structure\Select as SelectStructure;
use UKMNorge\Slack\Payload\Payload;

class SelectUsers extends Select
{
    const TYPE = 'channels_select';

    public $initial_channel;
    public $repsonse_url_enabled;
    
    /**
     * Set initial channel id
     *
     * @param String $channel_id
     * @return self
     */
    public function setInitialChannel(String $channel_id)
    {
        static::requireSingleSelect();
        $this->initial_channel = $channel_id;
        return $this;
    }

    /**
     * Get selected initial channel id
     *
     * @return String
     */
    public function getInitialChannel()
    {
        static::requireSingleSelect();
        return $this->initial_channel;
    }

    /**
     * Set whether response url should be enabled
     * 
     * NOTE: does only work for single-select in input blocks in modals
     * 
     * @see https://api.slack.com/reference/block-kit/block-elements#external_multi_select#conversation_select
     *
     * @param Bool $status
     * @return self
     */
    public function setResponseUrlEnabled(Bool $status)
    {
        if( !static::isSingleSelect()) {
            throw new Exception(
                'Enable response url is only available for single-select lists',
                'invalid_context'
            );
        }
        $this->response_url_enabled = $status;
        return $this;
    }

    /**
     * Get whether the response url should be enabled
     *
     * @return void
     */
    public function getResponseUrlEnabled()
    {
        return $this->response_url_enabled;
    }

    /**
     * Get export data
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export(); // type + action_id (if not null) + confirm (if not null)

        if (static::isSingleSelect()) {
            // Initial conversation
            if (!is_null($this->getInitialChannel())) {
                $data->initial_channel = Payload::convert($this->getInitialChannel());
            }

            // Response url
            if (!is_null($this->getResponseUrlEnabled())) {
                $data->response_url_enabled = Payload::convert($this->getResponseUrlEnabled());
            }
        }


        return $data;
    }
}
