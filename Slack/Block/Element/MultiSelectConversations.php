<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Payload\Payload;

class MultiSelectConversations extends SelectConversations
{

    public $initial_conversations;

    /**
     * Get a list of initial user ids
     *
     * @return ConversationIds
     */
    public function getInitialConversations()
    {
        if (is_null($this->initial_conversations)) {
            $this->initial_conversations = new ConversationIds();
        }
        return $this->initial_conversations;
    }

    /**
     * Get export data
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export(); // type + action_id (if not null) + confirm (if not null)

        if ($this->getInitialConversations()->getLength() > 0) {
            $data->initial_conversations = Payload::convert($this->getInitialConversations());
        }
        
        return $data;
    }
}
