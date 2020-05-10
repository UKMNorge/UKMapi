<?php

namespace UKMNorge\Slack\API\Response\Plugin\Filter;

use stdClass;
use UKMNorge\Slack\API\Response\Plugin\Transport\TransportInterface;

interface FilterInterface {
    public function getType();
    public function isAsync();
    /**
     * Conditions for filter processing
     *
     * @param TransportInterface transport data
     * @return Bool
    */
    public function condition( TransportInterface $transport);

    /**
     * Process data of interaction
     *
     * @param TransportInterface transport data
     * @return TransportInterface modified response
    */
    public function process( TransportInterface $transport );
}