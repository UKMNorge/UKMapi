<?php

namespace UKMNorge\OAuth2\IdentityProvider\Basic;

use Exception;
use stdClass;
use UKMNorge\OAuth2\IdentityProvider\Interfaces\Error as ErrorInterface;

class Error implements ErrorInterface
{
    private $message;
    private $code;
    private $additional_data;

    /**
     * Opprett en ny error
     * 
     * @param string $message
     * @param int $code
     * @param stdClass $additional_data
     * @return self
     */
    public function __construct(string $message, int $code, stdClass $additional_data = null)
    {
        $this->message = $message;
        $this->code = $code;
        $this->additional_data = $additional_data;
    }

    /**
     * Hent error-melding
     * 
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * Hent feilkode
     * 
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Hent ekstra-data
     * 
     * @return stdClass
     */
    public function getData(): stdClass
    {
        return $this->additional_data;
    }

    /**
     * Hent ekstra-data
     * 
     * @param stdClass $data
     * @return self
     */
    public function setData(stdClass $data): self
    {
        $this->additional_data = $data;
        return $this;
    }

    /**
     * Hent error-melding
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->getMessage();
    }

    /**
     * Kast erroren som en standard Exception
     * 
     * @throws Exception
     */
    public function throw()
    {
        throw new Exception($this->getMessage(), $this->getCode());
    }
}
