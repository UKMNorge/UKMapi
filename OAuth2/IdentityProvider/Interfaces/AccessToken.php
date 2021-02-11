<?php

namespace UKMNorge\OAuth2\IdentityProvider\Interfaces;

use stdClass;

interface AccessToken
{
    /**
     * Opprett ny accessToken
     * 
     * @param string $accessToken
     * @return self
     */
    public function __construct(string $accessToken);

    /**
     * Hent faktisk token
     * 
     * @return string
     */
    public function getToken(): string;

    /**
     * Hent faktisk token
     * 
     * @see getToken()
     * @return string
     */
    public function __toString(): string;

    /**
     * Angi ekstra data
     * 
     * @param stdClass $data
     * @return self
     */
    public function setData(stdClass $data);

    /**
     * Hent ekstra data
     * 
     * @return stdClass
     */
    public function getData(): stdClass;
}
