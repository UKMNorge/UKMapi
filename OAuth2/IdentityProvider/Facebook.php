<?php

namespace UKMNorge\OAuth2\IdentityProvider;

use UKMNorge\Http\Curl;
use UKMNorge\OAuth2\IdentityProvider\Basic\IdentityProvider;
use UKMNorge\OAuth2\IdentityProvider\Basic\User;
use UKMNorge\OAuth2\IdentityProvider\Basic\AccessToken;
use UKMNorge\OAuth2\IdentityProvider\Basic\Error;

class Facebook extends IdentityProvider
{
    private static $url_graph_api       = 'https://graph.facebook.com/v9.0/';
    private static $url_auth            = 'https://www.facebook.com/v9.0/dialog/oauth';
    private static $url_access_token    = 'https://graph.facebook.com/v9.0/oauth/access_token';
    private static $url_redirect        = 'https://id.' . UKM_HOSTNAME . '/auth/facebook/';
    private $scope = ['public_profile']; // default scope


    public function getAuthUrl(): string
    {
        return $this->url_auth .
            '?client_id=' . $this->getId() .
            '&redirect_uri=' . $this->getRedirectUrl(true) .
            '&scope=' . $this->getScope(true) .
            '&state=' . $this->getState();
    }

    /**
     * Hent state-param
     *
     * @return string
     */
    public function getState(): string
    {
        return substr(uniqid('', true), -5);
    }

    /**
     * Bytt code i AccessToken
     *
     * @param string $code
     * @param string $state
     * @return AccessToken
     */
    public function exchangeCodeForAccessToken(string $code, string $state = null): AccessToken
    {
        $request = new Curl();
        $request->timeout(4);
        $response = $request->process(
            $this->getAccessTokenUrl() .
                '?client_id=' . $this->getId() .
                '&redirect_uri=' . $this->getRedirectUrl() .
                '&client_secret=' . $this->getSecret() .
                '&code=' . $code
        );

        if (!$response || $response->error) {
            throw new Error(
                'En feil oppsto ved innlogging. Prøv igjen eller kontakt support@ukm.no',
                1
            );
        }

        $accessToken = new AccessToken($response->access_token);
        $accessToken->setData($response);

        return $accessToken;
    }

    /**
     * Hent current user fra facebook
     *
     * @return User
     */
    public function getCurrentUser(): User
    {
        $request = new Curl();
        $request->timeout(4);
        $userdata = $request->process(
            static::$url_graph_api .
                'me/' .
                '&fields=id,name,first_name,last_name' .
                '?access_token=' . $this->getAccessToken()
        );
        return new User($userdata->id, $userdata->first_name, $userdata->last_name);
    }
}
