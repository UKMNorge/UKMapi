<?php

namespace UKMNorge\OAuth2;


use UKMNorge\OAuth2\ID\UserManager;
use UKMNorge\OAuth2\Request;
use Exception;

require_once('/etc/php-includes/UKM/vendor/bshaffer/oauth2-server-php/src/OAuth2/Autoloader.php');
\OAuth2\Autoloader::register();


class HandleAPICall {
    private $request;
    private $method = null;
    private $clientArguments = [];
    private $optionalArguments = [];
    
    function __construct(array $requiredArguments, array $optionalArguments, array $acceptedMethods, bool $loginRequired, bool $wordpressLogin = false) {
        if($loginRequired && !UserManager::isUserLoggedin()){
            $this->sendErrorToClient('Du er ikke innlogget!', 401); // UNAUTHORIZED
        }

        // Wordpress login
        if($wordpressLogin != false) {
            if(is_user_logged_in() !== true) {
                $this->sendErrorToClient('Du er ikke innlogget!', 401); // UNAUTHORIZED
            }
        }


        $this->request = Request::createFromGlobals();
    
        $this->verifyMethod($acceptedMethods);
        $this->verifyRequiredArguments($requiredArguments);
        $this->initOptionalArguments($optionalArguments);
    }

    /**
     * Get argument by providing the key
     * @param string $key
     * @return string|null
     */
    public function getArgument(string $key) {
        return $this->clientArguments[$key] ? $this->clientArguments[$key] : null;
    }

    /**
     * Get optional argument by providing the key
     * @param string $key
     * @return string|null
     */
    public function getOptionalArgument(string $key) {
        try{
            $this->optionalArguments[$key];
        } catch(Exception $e) {
            throw new Exception($key . ' er ikke definert som et valgfritt argument');
        }
        
        try{
            return $this->request->requestRequired($key, $this->method);
        } catch(Exception $e) {
            return null;
        }
    }

    /**
     * Return the answer to the client
     * IMPORTANT: This method will stop all the execution (another call from the stack) and return to the client
     * @param string|array $data
     * @param string $statusCode
     * @return void
     */
    public function sendToClient($data, int $statusCode = 200) : void {
        http_response_code($statusCode);
        if($statusCode >= 400) { // Codes That Represent Errors (4xx and 5xx)
            $data = is_array($data) ? $data : array('errorMessage' => $data);
        } else {
            $data = is_array($data) ? $data : array('result' => $data);
        }

        die(json_encode($data));
    }

    /**
     * Return the answer with error code to the client
     * IMPORTANT: This method will stop all the execution by calling sendToClient() method
     * @param array $message
     * @param string $statusCode
     * @return void
     */
    public function sendErrorToClient($message, int $statusCode) : void {
        $this->sendToClient(is_array($message) ? $message : $message, $statusCode);
    }

    /**
     * Verify if the method provided by the client is inside the array
     * @param array $acceptedMethods
     * @return void
     */
    private function verifyMethod(array $acceptedMethods) : void {
        $method = $this->request->server['REQUEST_METHOD'];

        foreach($acceptedMethods as $m) {
            if($m == $method) {
                $this->method = $method;
                break;
            }
        }

        if(!$this->method) {
            $this->sendErrorToClient('Method not allowed', 405);
        }
    }

    /**
     * Verify if all required arguments are provided by the client
     * @param array $requiredArguments
     * @return void
     */
    private function verifyRequiredArguments(array $requiredArguments) : void {
        try{
            foreach($requiredArguments as $arg) {
                $this->clientArguments[$arg] = $this->request->requestRequired($arg, $this->method);
            }
        }
        catch(Exception $e) {
            $this->sendErrorToClient($e->getMessage(), 400);
        }
    }


    /**
     * Add all optional arguments
     * @param array $requiredArguments
     * @return void
     */
    private function initOptionalArguments(array $optionalArguments) {
        foreach($optionalArguments as $arg) {
            $this->optionalArguments[$arg] = true;
        }
    }

}