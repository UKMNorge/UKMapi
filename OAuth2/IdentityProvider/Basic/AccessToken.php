<?php

namespace UKMNorge\OAuth2\IdentityProvider\Basic;

use stdClass;
use UKMNorge\OAuth2\IdentityProvider\Interfaces\AccessToken as AccessTokenInterface;

class AccessToken implements AccessTokenInterface
{
    private $token;
    private $data;

    /**
     * Opprett ny accessToken
     * 
     * @param string $accessToken
     * @return self
     */
    public function __construct(String $accessToken)
    {
        $this->token = $accessToken;
    }

    /**
     * Hent faktisk token
     * 
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Hent faktisk token
     * 
     * @see getToken()
     * @return string
     */
    public function __toString(): string
    {
        return $this->getToken();
    }

    /**
     * Angi ekstra data
     * 
     * @param stdClass $data
     * @return self
     */
    public function setData(stdClass $data): self
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Hent ekstra data
     * 
     * @return stdClass
     */
    public function getData(): stdClass
    {
        return $this->data;
    }
}
