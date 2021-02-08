<?php

namespace UKMNorge\OAuth2\IdentityProvider\Interfaces;

use Exception;
use stdClass;

interface Error
{

    /**
     * Opprett en ny error
     * 
     * @param string $message
     * @param int $code
     * @param array $additional_data
     * @return self
     */
    public function __construct(string $message, int $code, array $additional_data = null);

    /**
     * Hent error-melding
     * 
     * @return string
     */
    public function getMessage(): string;

    /**
     * Hent feilkode
     * 
     * @return int
     */
    public function getCode(): int;

    /**
     * Hent ekstra-data
     * 
     * @return stdClass
     */
    public function getData(): stdClass;

    /**
     * Hent ekstra-data
     * 
     * @param stdClass $data
     * @return self
     */
    public function setData(stdClass $data): self;

    /**
     * Hent error-melding
     * 
     * @return string
     */
    public function __toString(): string;

    /**
     * Kast erroren som en standard Exception
     * 
     * @throws Exception
     */
    public function throw();
}
