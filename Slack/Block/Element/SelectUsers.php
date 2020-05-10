<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Structure\Collection\UserIds;
use UKMNorge\Slack\Block\Structure\Select as SelectStructure;
use UKMNorge\Slack\Block\Payload\Payload;

class SelectUsers extends SelectStructure
{
    const TYPE = 'users_select';

    public $initial_user;

    /**
     * Set initially selected user
     *
     * @param String $user_id
     * @return self
     */
    public function setInitialUser( String $user_id ) {
        $this->initial_user = $user_id;
        return $this;
    }

    /**
     * Get initially selected user id
     *
     * @return String
     */
    public function getInitialUser() {
        return $this->initial_user;
    }

    /**
     * Get export data
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export(); // type + action_id (if not null) + confirm (if not null)

        if ( $this->isSingleSelect() && !is_null($this->getInitialUser())) {
            $data->initial_user = Payload::convert($this->getInitialUser());
        }

        return $data;
    }
}
