<?php

namespace UKMNorge\OAuth2\IdentityProvider\Interfaces;

use UKMNorge\Oauth2\IdentityProvider\Interfaces\User;

interface IdentityProvider
{

    /**
     * Opprett en ny IP
     * 
     * @param string $id
     * @param string $secret
     * @return self
     */
    public function __construct(string $id, string $secret);

    /**
     * Bytt code i en access token
     * 
     * @param string $code
     * @param string $state
     * @throws Error
     * @return AccessToken $token
     */
    public function exchangeCodeForAccessToken(string $code, string $state = null): AccessToken;

    /**
     * URL som vi sender brukeren til for autentisering og autorisering (?)
     * 
     * @return string $url
     */
    public function getAuthUrl(): string;


    /**
     * Angi scope som skal brukes
     * 
     * @param array $scope
     * @return self
     */
    public function setScope(array $scope): self;


    /**
     * Hent innlogget bruker fra IdentityProvider
     * 
     * @throws Error
     * @return User
     */
    public function getCurrentUser(): User;

    /**
     * Angi access token
     *
     * @param AccessToken $accessToken
     * @return self
     */
    public function setAccessToken(AccessToken $accessToken): self;

    /**
     * Sjekk hvorvidt vi har en access token
     *
     * @return bool
     */
    public function hasAccesstoken(): bool;
}
