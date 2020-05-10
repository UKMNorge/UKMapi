<?php

namespace UKMNorge\Slack\API\Response\Plugin\Filter;

use UKMNorge\Slack\API\Response\Plugin\Transport\TransportInterface;

abstract class Trigger extends Filter {
    const TYPE = 'Trigger';
    abstract public function process( TransportInterface $transport );
}