<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Composition\Filter;
use UKMNorge\Slack\Block\Structure\Select as SelectStructure;
use UKMNorge\Slack\Payload\Payload;

class SelectConversations extends SelectStructure
{
    const TYPE = 'conversations_select';

    public $default_to_current_conversation;
    public $filter;
    public $response_url_enabled;
    public $initial_conversation;

    /**
     * Set whether to pre-populate the select with current conversation
     *
     * @param Bool $status
     * @return self
     */
    public function setDefaultToCurrentConversation(Bool $status)
    {
        $this->default_to_current_conversation = $status;
        return $this;
    }

    /**
     * Whether to pre-populate the select with current conversation
     *
     * @return Bool
     */
    public function getDefaultToCurrentConversation()
    {
        return $this->default_to_current_conversation;
    }

    /**
     * Set conversations filter
     *
     * @param Filter $filter
     * @return self
     */
    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * Get conversations filter
     *
     * @return Filter
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set initial conversation id
     *
     * @param String $conversation_id
     * @return self
     */
    public function setInitialConversation(String $conversation_id)
    {
        static::requireSingleSelect();
        $this->initial_conversation = $conversation_id;
        return $this;
    }

    /**
     * Get selected initial conversation id
     *
     * @return String
     */
    public function getInitialConversation()
    {
        static::requireSingleSelect();
        return $this->initial_conversation;
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

        // Default to current conversation
        if (!is_null($this->getDefaultToCurrentConversation())) {
            $data->default_to_current_conversation = Payload::convert($this->getDefaultToCurrentConversation());
        }

        // Filter
        if (!is_null($this->getFilter())) {
            $data->filter = Payload::convert($this->getFilter());
        }

        if (static::isSingleSelect()) {
            // Initial conversation
            if (!is_null($this->getInitialConversation())) {
                $data->initial_conversation = Payload::convert($this->getInitialConversation());
            }

            // Response url
            if (!is_null($this->getResponseUrlEnabled())) {
                $data->response_url_enabled = Payload::convert($this->getResponseUrlEnabled());
            }
        }


        return $data;
    }
}
