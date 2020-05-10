<?php

namespace UKMNorge\Slack\API\Response\Plugin\Transport;

interface TransportInterface {
    public function getResponse();
    public function getData();
    public function getId();
}