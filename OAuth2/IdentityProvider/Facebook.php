<?php

namespace UKMNorge\OAuth2\IdentityProvider;

use DateTime;
use UKMNorge\Http\Curl;
use UKMNorge\OAuth2\IdentityProvider\Basic\IdentityProvider;
use UKMNorge\OAuth2\IdentityProvider\Basic\User;
use UKMNorge\OAuth2\IdentityProvider\Basic\AccessToken;
use UKMNorge\OAuth2\IdentityProvider\Basic\Error;

use UKMNorge\OAuth2\IdentityProvider\Interfaces\AccessToken as AccessTokenInterface;
use UKMNorge\Oauth2\IdentityProvider\Interfaces\User as UserInterface;


class Facebook extends IdentityProvider
{
    private static $url_graph_api       = 'https://graph.facebook.com/v9.0/';
    protected static $url_auth            = 'https://www.facebook.com/v9.0/dialog/oauth';
    protected static $url_access_token    = 'https://graph.facebook.com/v9.0/oauth/access_token';
    protected static $url_redirect        = 'https://id.' . UKM_HOSTNAME . '/auth/facebook/';
    protected $scope = ['public_profile']; // default scope


    public function getAuthUrl(): string
    {
        return static::$url_auth .
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
    public function exchangeCodeForAccessToken(string $code, string $state = null) : AccessTokenInterface
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
     * @throws Error
     * @return User
     */
    public function getCurrentUser(): UserInterface
    {
        $request = new Curl();
        $request->timeout(4);
        $userdata = $request->process(
            static::$url_graph_api .
                'me/' .
                '&fields=id,name,first_name,last_name,birthday' .
                '?access_token=' . $this->getAccessToken()
        );
        // TODO: Håndter feil fra facebook her
        // Kast Error hvis noe har gått galt
        $user = new User($userdata->id, $userdata->first_name, $userdata->last_name);
        if ($userdata->birthday) {
            $user->setDateOfBirth(DateTime::createFromFormat('MM/DD/YYYY', $userdata->birthday));
        }
        return $user;
    }
}
