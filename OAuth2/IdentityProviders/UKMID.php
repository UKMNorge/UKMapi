<?php

namespace UKMNorge\OAuth2\IdentityProvider;

use UKMNorge\Http\Curl;
use UKMNorge\OAuth2\IdentityProvider\Basic\IdentityProvider;
use UKMNorge\OAuth2\IdentityProvider\Basic\User;

/**
 * FORVIRRENDE AT DENNE LIGGER HER, SÅ VI MÅ NOK FLYTTE DEN PÅ ET TIDSPUNKT
 * 
 * DENNE SKAL BLANT ANNET BRUKES AV FIREWALL I DELTA PÅ SYMFONY 5
 * 
 */
class UKMID extends IdentityProvider {
    private static $url_auth            = 'https://id.' . UKM_HOSTNAME . '/auth.php';
    private static $url_access_token    = 'https://id.' . UKM_HOSTNAME . '/api/auth/access-token.php';
    private static $url_redirect        = 'https://delta.ukm.dev/auth/provider/ukmid/';

    /**
     * Hent current user fra 
     *
     * @return User
     */
    public function getCurrentUser(): User
    {
        $userdata = $this->request('me.php');
        return new User($userdata->id, $userdata->first_name, $userdata->last_name);
    } 

    /**
     * Gjør en forespørsel til UKM ID-api
     *
     * @param string $endpoint
     * @return Curl
     */
    public function request(string $endpoint): Curl {
        $request = new Curl();
        $request->timeout(4);
        return $request->process(
            $this->getAuthUrl() .
            $endpoint .
            '?access_token='. $this->getAccessToken()
        );
    }
}