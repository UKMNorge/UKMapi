<?php

namespace UKMNorge\API\Mailchimp;

use Exception;
use UKMNorge\API\Mailchimp\Collection;

class Audiences extends Collection {
    public $resource = "lists";
    public $result_key = 'lists';

    public function createFromAPIData($row)
    {
        return Audience::createFromAPIdata( $row );
    }
}