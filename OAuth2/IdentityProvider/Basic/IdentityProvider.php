<?php

namespace UKMNorge\OAuth2\IdentityProvider\Basic;

use UKMNorge\Http\Curl;
use UKMNorge\OAuth2\IdentityProvider\Interfaces\AccessToken;
use UKMNorge\OAuth2\IdentityProvider\Interfaces\IdentityProvider as IdentityProviderInterface;
use UKMNorge\Oauth2\IdentityProvider\Interfaces\User;

abstract class IdentityProvider implements IdentityProviderInterface
{

    /**
     * URL vi sender brukeren til (uten scope, redirect_uri osv)
     * 
     * @var String $url_auth
     */
    protected static $url_auth;

    /**
     * URL hvor brukeren skal sendes tilbake til
     * 
     * @var String $url_redirect
     */
    protected static $url_redirect;

    /**
     * URL vi sender curl-request til for å bytte code i accessToken
     * 
     * @var String $url_token
     */
    protected static $url_access_token;

    /**
     * Array med scope vi etterspør fra identity provider
     * 
     * @var Array $scope
     */
    protected $scope;

    /**
     * App ID
     * 
     * @var String $id
     */
    protected $id;

    /**
     * App Secret
     * 
     * @var String $secret
     */
    protected $secret;

    /**
     * Access Token (hvis satt)
     *
     * @var AccessToken
     */
    protected $accessToken;

    /**
     * Opprett en ny IP-instans
     * 
     * @param String $id
     * @param String $secret
     * @return self
     */
    public function __construct(String $id, String $secret)
    {
        $this->id = $id;
        $this->secret = $secret;
    }

    /**
     * Sett scope
     * 
     * @param array $scope
     * @return self
     */
    public function setScope(array $scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * Hent scope
     * 
     * @param bool $as_string
     * @return array|string
     */
    public function getScope(bool $as_string = false)
    {
        if ($as_string) {
            return implode(',', $this->scope);
        }
        return $this->scope;
    }

    /**
     * Hent redirect URL
     * 
     * @param bool $url_encoded
     * @return string $url
     */
    public function getRedirectUrl(bool $url_encoded = false): string
    {
        if ($url_encoded) {
            return urlencode(static::$url_redirect);
        }
        return static::$url_redirect;
    }

    /**
     * URL som vi sender brukeren til for autentisering og autorisering (?)
     * 
     * @return string $url
     */
    public function getAuthUrl(): string
    {
        return $this->url_auth .
            '?redirect_uri=' . $this->getRedirectUrl(true) .
            '&scope=' . implode(',', $this->getScope());
    }

    /**
     * Hent URL som brukes for å hente access token
     * 
     * @return string
     */
    public function getAccessTokenUrl(): string
    {
        return static::$url_access_token;
    }

    /**
     * Hent app id
     * 
     * @return string
     */
    protected function getId(): string
    {
        return $this->id;
    }

    /**
     * Hent app secret
     * 
     * @return string
     */
    protected function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * Send code til identity provider for å få tilbake en access token
     * 
     * @param string $code
     * @param string $state
     * @throws Error
     * @return Accesstoken
     */
    public function exchangeCodeForAccessToken(string $code, string $state = null): AccessToken
    {
        $request = new Curl();
        $request->timeout(4);
        $request->user($this->getId() . ':' . $this->getSecret());
        $request->post([
            'redirect_uri' => $this->getRedirectUrl(),
            'code' => $code
        ]);

        $response = $request->process(
            $this->getAccessTokenUrl() .
                '?code=' . $code
        );

        if (!$response /* || better check of response */) {
            throw new Error(
                'En feil oppsto ved henting av accesstoken',
                1
            );
        }

        $accessToken = new AccessToken($response->access_token);
        $accessToken->setData($response);

        return $accessToken;
    }

    /**
     * Angi access token
     *
     * @param AccessToken $accessToken
     * @return self
     */
    public function setAccessToken(AccessToken $accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Hent access token
     * Defineres protected function for sikkerhetsårsaker
     *
     * @return AccessToken
     */
    protected function getAccessToken(): AccessToken
    {
        return $this->accessToken;
    }

    /**
     * Sjekk hvorvidt vi har en access token
     *
     * @return bool
     */
    public function hasAccesstoken(): bool
    {
        return !is_null($this->accessToken);
    }
}
