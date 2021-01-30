<?php

# NOTE: IF THE GRANT TYPE USER CREDENTIALS IS NOT USED, THEN THIS CLASS CAN BE DELETED

namespace UKMNorge\OAuth2;

use \OAuth2\GrantType\AuthorizationCode as BshafferAuthorizationCode;
use \OAuth2\RequestInterface;
use \OAuth2\ResponseInterface;
use OAuth2\ResponseType\AccessTokenInterface;
use UKMNorge\OAuth2\Interfaces\AuthorizationCodeInterface;

// This class extends UserCredentials from Bshaffer
class AuthorizationCode extends BshafferAuthorizationCode {
    
    /**
     * @var array
     */
    private $userInfo;

    /**
     * @var AuthorizationCodeInterface
     */
    protected $storage;

    public function __construct(AuthorizationCodeInterface $storage) {
        parent::__construct($storage);
    }
    
    
    /**
     * Override
     * There is no password and username needed. The user will be identified based on session
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @return bool|mixed|null
     *
     * @throws LogicException
     */
    public function validateRequest(RequestInterface $request, ResponseInterface $response) {
        
        $tel_nr = $this->storage->getUserLoggedinTelNr(); 
         
         if (empty($tel_nr) || !$this->storage->isUserLoggedin()) {
             $response->setError(401, 'invalid_grant', 'The user is not logged in!');
 
             return null;
         }

         $userInfo = $this->storage->getUserDetails($tel_nr);
 
         if (empty($userInfo)) {
             $response->setError(400, 'invalid_grant', 'Unable to retrieve user information');
 
             return null;
         }
 
         if (!isset($userInfo['user_id'])) {
             throw new \LogicException("you must set the user_id on the array returned by getUserDetails");
         }
 
         $this->userInfo = $userInfo;
 
         return true;
    }

    /**
     * Get user id
     *
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userInfo['user_id'];
    }
    
}