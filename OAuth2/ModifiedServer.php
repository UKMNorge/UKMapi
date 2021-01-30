<?php

namespace UKMNorge\OAuth2;

use \OAuth2\Server as BshafferServer;
// use AuthorizeController as AuthorizeController;

use \OAuth2\RequestInterface;
use \OAuth2\ResponseInterface;
use \OAuth2\OpenID\Controller\AuthorizeController as OpenIDAuthorizeController;


require_once('UKM/vendor/autoload.php');

class ModifiedServer extends BshafferServer {
    
     /**
     * Redirect the user appropriately after approval.
     *
     * After the user has approved or denied the resource request the
     * authorization server should call this function to redirect the user
     * appropriately.
     *
     * @param RequestInterface  $request - The request should have the follow parameters set in the querystring:
     * - response_type: The requested response: an access token, an authorization code, or both.
     * - client_id: The client identifier as described in Section 2.
     * - redirect_uri: An absolute URI to which the authorization server will redirect the user-agent to when the
     *   end-user authorization step is completed.
     * - scope: (optional) The scope of the resource request expressed as a list of space-delimited strings.
     * - state: (optional) An opaque value used by the client to maintain state between the request and callback.
     *
     * @param ResponseInterface $response      - Response object
     * @param bool              $is_authorized - TRUE or FALSE depending on whether the user authorized the access.
     * @param mixed             $user_id       - Identifier of user who authorized the client
     * @return ResponseInterface
     *
     * @see http://tools.ietf.org/html/rfc6749#section-4
     *
     * @ingroup oauth2_section_4
     */
    public function handleAuthorizeRequest(RequestInterface $request, ResponseInterface $response, $is_authorized, $user_id = null)
    {
        $this->response = $response;
        $this->getAuthorizeController()->handleAuthorizeRequest($request, $this->response, $is_authorized, $user_id);

        return $this->response;
    }

    /**
     * @return AuthorizeControllerInterface
     */
    public function getAuthorizeController()
    {
        echo 'hellooooww';
        if (is_null($this->authorizeController)) {
            $this->authorizeController = $this->createDefaultAuthorizeController();
        }

        return $this->authorizeController;
    }

    /**
     * @return AuthorizeControllerInterface
     * @throws LogicException
     */
    protected function createDefaultAuthorizeController()
    {
        if (!isset($this->storages['client'])) {
            throw new \LogicException('You must supply a storage object implementing \OAuth2\Storage\ClientInterface to use the authorize server');
        }
        if (0 == count($this->responseTypes)) {
            $this->responseTypes = $this->getDefaultResponseTypes();
        }
        if ($this->config['use_openid_connect'] && !isset($this->responseTypes['id_token'])) {
            $this->responseTypes['id_token'] = $this->createDefaultIdTokenResponseType();
            if ($this->config['allow_implicit']) {
                $this->responseTypes['id_token token'] = $this->createDefaultIdTokenTokenResponseType();
            }
        }

        $config = array_intersect_key($this->config, array_flip(explode(' ', 'allow_implicit enforce_state require_exact_redirect_uri')));

        if ($this->config['use_openid_connect']) {
            return new OpenIDAuthorizeController($this->storages['client'], $this->responseTypes, $config, $this->getScopeUtil());
        }

        return new AuthorizeController($this->storages['client'], $this->responseTypes, $config, $this->getScopeUtil());
    }

}
