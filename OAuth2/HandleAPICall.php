<?php

namespace UKMNorge\OAuth2;

use UKMNorge\OAuth2\ID\UserManager;
use UKMNorge\OAuth2\Request;
use Exception;

class HandleAPICall {
    private $request;
    private $method = null;
    private $clientArguments = [];
    
    function __construct(array $requiredArguments, array $optionalAttributes, array $acceptedMethods, bool $loginRequired) {
        if($loginRequired && !UserManager::isUserLoggedin()){
            http_response_code(401); // UNAUTHORIZED
            die;
        }

        $this->request = Request::createFromGlobals();
    
        $this->verifyMethod($acceptedMethods);
        $this->verifyRequiredArguments($requiredArguments);
    }

    public function getArgument(string $key) {
        return $this->clientArguments[$key];
    }

    /**
     * Return the answer to the client
     * @param string|array $data
     * @param string $statusCode
     * @return void
     */
    public function sendToClient($data, int $statusCode = 200) : void {
        http_response_code($statusCode);
        $data = is_array($data) ? $data : array('result' => $data);
        die(json_encode($data));
    }

    /**
     * Return the answer with error code to the client
     * @param array $message
     * @param string $statusCode
     * @return void
     */
    public function sendErrorToClient(string $message, int $statusCode) : void {
        $this->sendToClient(array('details' => $message), $statusCode);
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
            // http_response_code(400); // BAD REQUEST (missing required arguments)
            $this->sendErrorToClient($e->getMessage(), 400);
        }
    }

}