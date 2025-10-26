<?php

namespace UKMNorge\OAuth2;


use UKMNorge\OAuth2\ID\UserManager;
use UKMNorge\OAuth2\Request;
use Exception;

require_once('UKMconfig.inc.php');

require_once('/etc/php-includes/UKM/vendor/bshaffer/oauth2-server-php/src/OAuth2/Autoloader.php');
\OAuth2\Autoloader::register();


class HandleAPICall {
    private $request;
    private $method = null;
    private $clientArguments = [];
    private $optionalArguments = [];
    protected $apiKeyRequired = false;
    
    /**
     * HandleAPICall constructor.
     * @param array $requiredArguments - required arguments from the client
     * @param array $optionalArguments - optional arguments from the client
     * @param array $acceptedMethods - accepted methods from the client
     * @param bool $loginRequired - If true, the user must be logged in to arr-sys
     * @param bool $wordpressLogin - default false. Brukes for å sjekke om brukeren er logget inn i Wordpress.
     * @param bool $apiKeyRequired - default false. Det brukes for å hente protected data fra et API kall.
     */
    function __construct(array $requiredArguments, array $optionalArguments, array $acceptedMethods, bool $loginRequired, bool $wordpressLogin = false, $apiKeyRequired = false) {
        if($loginRequired && !UserManager::isUserLoggedin()){
            $this->sendErrorToClient('Du er ikke innlogget!', 401); // UNAUTHORIZED
        }
        $this->apiKeyRequired = $apiKeyRequired;
        // Check if API key is required
        $this->authorizeRequest();

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
        $data = is_array($data) ? $data : array('result' => $data);
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

    /**
     * Check if the API key is valid
     * @return void
     */
    public function authorizeRequest() {
        if(!$this->apiKeyRequired) { 
            return;
        }

        $validKeys = [
            ARRSYS_API_TOKEN,
        ];

        $providedKey = $_GET['api_key'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;

        if (!$providedKey || !in_array($providedKey, $validKeys)) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
    }

    /**
     * Limit requests from the same IP address on a specific form to prevent abuse
     * 
     * The method stops the execution and returns a 429 error if the limit is exceeded
     * 
     * @param string|null $formIdentifier
     * @return void
     */
    public function limitRequestsFromIP(?string $formIdentifier = null) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $uri = $_SERVER['REQUEST_URI'] ?? 'unknown';

        $formKey = $formIdentifier ?: $uri;

        // Build a unique key per IP + form
        $key = "rate_limit:{$ip}:{$formKey}";

        $limit = 10;  // requests per minute per IP per form
        $ttl   = 60;  // seconds

        // Fetch or initialize
        $count = apcu_fetch($key);

        if ($count === false) {
            apcu_store($key, 1, $ttl);
        } elseif ($count >= $limit) {
            http_response_code(429);
            $this->sendToClient(['error' => 'Too many requests'], 429);
            exit;
        } else {
            apcu_inc($key);
        }
    }

}