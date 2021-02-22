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

use Google\Client as GoogleClient; 
date_default_timezone_set('Europe/Oslo');

ini_set("display_errors", true);

require_once('UKMconfig.inc.php');

use Google\AccessToken\Verify as VerifyTest;

use Exception;
use stdClass;


class Google extends IdentityProvider
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
        $client = new GoogleClient($this->getCredentials());
        $client->setApplicationName('People API PHP Quickstart');
        $client->setRedirectUri('https://id.ukm.dev');
        $client->setScopes(["https://www.googleapis.com/auth/userinfo.profile"]);
        $client->setAccessType('offline');
        
        $response = $client->fetchAccessTokenWithAuthCode($code);
        
        
        if (isset($response['error'])) {
            throw new Exception(
                'En feil oppsto ved innlogging. Prøv igjen eller kontakt support@ukm.no'
            );
            
        }
        return new AccessToken($response['access_token'], $response['id_token']);

    }

    public function getUserTest($accessToken, $id_token) {

    }

    /**
     * Hent current user fra facebook
     *
     * @throws Error
     * @return User
     */
    public function getCurrentUser(): UserInterface
    {
        $client = new GoogleClient($this->getCredentials());
        $client->setApplicationName('People API PHP Quickstart');
        // $client->setAuthConfig('credentials.json');
        $client->setRedirectUri('https://id.ukm.dev');
        $client->setScopes(["https://www.googleapis.com/auth/userinfo.profile"]);
        $client->setAccessType('offline');


        $accessTokenObj = $this->getAccessToken();

        $accessToken = $accessTokenObj->getToken();
        $idToken = $accessTokenObj->getIdToken();

        $client->setAccessToken(
            [
              'access_token' => $accessToken,
              'expires_in' => 3600, // Google default
              'created' => time(),
            ]
        );


        $payload = $client->verifyIdToken($idToken);
        
        if ($payload) {
            $userId = $payload['sub'];
            $user = new User($userId, $payload['given_name'], $payload['family_name']);
            $user->setDateOfBirth(DateTime::createFromFormat('Y-m-d', '2000-01-01'));

            return $user;
        }
        throw new Exception('Invalid token');
    }

    private function getCredentials() {
        return array (
            'client_id' => $this->id,
            'project_id' => 'ukm-arrangemente-1567942518161',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token',
            'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
            'client_secret' => $this->secret,
            'redirect_uris' => 
            array (
            0 => 'https://id.ukm.no',
            1 => 'https://id.ukm.dev',
            ),
            'javascript_origins' => 
            array (
            0 => 'https://id.ukm.no',
            1 => 'https://id.ukm.dev',
            ),
        );
    }
}
