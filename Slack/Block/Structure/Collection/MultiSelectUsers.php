<?php

namespace UKMNorge\Slack\Block\Element;

use UKMNorge\Slack\Block\Structure\Collection\UserIds;
use UKMNorge\Slack\Block\Structure\Exception;

class MultiSelectUsers extends MultiSelect
{
    const TYPE = 'multi_users_select';
    const REQUIRE_ACTION_ID = true;

    public $initial_users;

    /**
     * Get a list of initial user ids
     *
     * @return UserIds
     */
    public function getInitialUsers() {
        if( is_null($this->initial_users)) {
            $this->initial_users = new UserIds();
        }
        return $this->initial_users;
    }

    /**
     * Get export data
     *
     * @return ExportData
     */
    public function __toArray()
    {
        $data = parent::__toArray(); // type + action_id (if not null) + confirm (if not null)
 
        if( $this->getInitialUsers()->getLength() > 0 ) {
            $data->add('initial_users', $this->getInitialUsers());
        }

        return $data;
    }
}
