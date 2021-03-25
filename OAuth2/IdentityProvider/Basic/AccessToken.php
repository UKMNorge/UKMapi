<?php

namespace UKMNorge\OAuth2\IdentityProvider\Basic;

use stdClass;
use UKMNorge\OAuth2\IdentityProvider\Interfaces\AccessToken as AccessTokenInterface;

class AccessToken implements AccessTokenInterface
{
    private $token;
    private $idToken;
    private $data;

    /**
     * Opprett ny accessToken
     * 
     * @param string $accessToken
     * @return self
     */
    public function __construct(string $accessToken, $idToken = null)
    {
        $this->token = $accessToken;
        $this->idToken = $idToken;
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
     * Hent id token
     * 
     * @return string
     */
    public function getIdToken(): string {
        return $this->idToken ? $this->idToken : '-1';
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
    public function setData(stdClass $data)
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
