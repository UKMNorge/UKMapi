<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Structure\Collection\UserIds;
use UKMNorge\Slack\Payload\Payload;

class MultiSelectUsers extends SelectUsers {

    const TYPE = 'multi_users_select';
    const IS_MULTI_SELECT = true;

    public $initial_users;

    /**
     * Get a list of initial user ids
     *
     * @return UserIds
     */
    public function getInitialUsers()
    {
        if (is_null($this->initial_users)) {
            $this->initial_users = new UserIds();
        }
        return $this->initial_users;
    }

    /**
     * Return export data
     *
     * @return stdClass
    */
    public function export()
    {
        $data = parent::export();

        if ( $this->getInitialUsers()->getLength() > 0) {
            $data->initial_users = Payload::convert($this->getInitialUsers());
        }

        return $data;
    }

}