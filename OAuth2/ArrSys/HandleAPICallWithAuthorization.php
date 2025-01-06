<?php

namespace UKMNorge\OAuth2\ArrSys;

use UKMNorge\OAuth2\HandleAPICall;
use UKMNorge\OAuth2\ArrSys\AccessControlArrSys;
use UKMNorge\OAuth2\Request;
use UKMNorge\Arrangement\Arrangement;

use Exception;
use UKMNorge\Geografi\Kommune;

class HandleAPICallWithAuthorization extends HandleAPICall {
    private $accessType;
    private $accessValues;


    /**
     * Constructs a new instance of the API call handler with authorization.
     * 
     * IMPORTANT: The initialization fails if the user does not have the required access level, so you don't have to take of infoming the client about the error.
     *
     * This constructor initializes the API call handler with required and optional arguments,
     * accepted HTTP methods, and login requirements. It also performs an access check to ensure
     * the user has the necessary permissions. If the user does
     * not have the required access, an error is sent to the client.
     *
     * IMPORTANT: If the user does not have the required access level, the error is sent to the client and exection is stopped via sendErrorToClient().
     * 
     * @param array $requiredArguments An array of required arguments for the API call.
     * @param array $optionalArguments An array of optional arguments for the API call.
     * @param array $acceptedMethods An array of accepted HTTP methods (e.g., GET, POST).
     * @param bool $loginRequired Indicates whether login is required to access the API.
     * @param bool $wordpressLogin Indicates whether WordPress login is required.
     * @param string|null $accessType The type of access required (e.g., 'fylke' for regional, 'kommune' for local).
     * @param string|null $accessValue Additional values required for access verification (e.g., fylke ID).
     */
    function __construct(array $requiredArguments, array $optionalArguments, array $acceptedMethods, bool $loginRequired, bool $wordpressLogin = false, string $accessType = null, string $accessValue = null) {
        parent::__construct($requiredArguments, $optionalArguments, $acceptedMethods, $loginRequired, $wordpressLogin);
        
        try{
            if($this->verifyAccess($accessType, $accessValue) !== true) {
                $this->sendErrorToClient('Tilgangen er ikke gitt', 401);

            }
        }catch(Exception $e) {
            $this->sendErrorToClient($e->getMessage(), 401);
        }
    }


    /**
     * Verifies if the current user has access to the requested access level.
     * 
     * IMPORTANT: If the access type is not defined, access is granted. It means that there is no access control.
     * 
     * IMPORTANT: Superuser has always access.
     * 
     * 
     * This method checks if the user is a super admin or has the required access level
     * If the user does not have the necessary permissions, an exception is thrown.
     * 
     * @param string $accessType The type of access required (E.g. 'fylke', 'kommune').
     * @return bool Returns true if the user has the required access, otherwise throws an exception.
     * @throws Exception If the user does not have the required access level.
     */
    private function verifyAccess(string $accessType, $accessValue) {
        if(is_user_logged_in() !== true) {
            throw new Exception("Du er ikke innlogget!", 401);
        }

        // Superadmin har alltid tilgang
        if(is_super_admin()) {
            return true;
        }


        // Det kreves å være superadmin men brukeren er ikke superadmin
        if($accessType == 'superadmin') {
            throw new Exception("Du har ikke rettigheter som superadmin!", 401);
        }

        // Access is not defined, therefore access is granted
        if($accessType == null) {
            return true;
        }
        
        // FYLKE TILGANG
        if($accessType == 'fylke') {
            // Tilgang til minst 1 fylke generelt
            if($accessValue == null) {
                if(AccessControlArrSys::hasFylkeAccess() === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til fylker");
            }
            // Tilgang til spesifikt fylke
            else {
                if(AccessControlArrSys::hasAccessToFylke($accessValue) === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til fylket $accessValue");
            }
        }

        // KOMMUNE TILGANG
        if($accessType == 'kommune') {
            if($accessValue == null) {
                if(AccessControlArrSys::hasKommuneAccess() === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til kommuner");
            }
            else {
                if(AccessControlArrSys::hasAccessToKommune($accessValue) === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til kommune $accessValue");
            }
        }

        // Har tilgang i en kommune eller i et fylke som kommunen er del av
        // Hvis $accessValue er null, så sjekkes om brukeren har tilgang til minst 1 kommune eller 1 fylke
        // Eksempel: Er admin i Bergen eller Vestland. Hvis accessValue er null, så sjekkes om brukeren er admin i minst 1 kommune eller 1 fylke
        if($accessType == 'kommune_eller_fylke') {
            if($accessValue == null) {
                if(AccessControlArrSys::hasKommuneAccess() === true) {
                    return true;
                }
                if(AccessControlArrSys::hasFylkeAccess() === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til kommuner eller fylker", 401);
            }

            $kommune = null;
            try{
                $kommune = new Kommune($accessValue);
            } catch(Exception $e) {
                throw new Exception("Kunne ikke hente kommune med id $accessValue", 401);
            }
            // Er admin i kommune eller er admin i fylke som kommunen er del av
            if(AccessControlArrSys::hasAccessToKommune($accessValue) === true || AccessControlArrSys::hasAccessToFylke($kommune->getFylke()->getId()) === true) {
                return true;
            }
            throw new Exception("Du har ikke tilgang til kommune ". $kommune->getNavn() ." eller fylke " . $kommune->getFylke()->getNavn());
        
        }

        // Har tilgang i en kommune som er del av dette ($accessValue) fylke
        // Hvis brukerne har tilgang direkte til fylke, så har de tilgang til alle kommuner i fylket
        if($accessType == 'fylke_fra_kommune') {
            // Har tilgang direkt til fylke
            if(AccessControlArrSys::hasAccessToFylke($accessValue) === true) {
                return true;
            }
            // Har tilgang til fylke fra kommune
            if(AccessControlArrSys::hasAccessToFylkeFromKommune($accessValue) === true) {
                return true;
            }
            throw new Exception("Du har ikke tilgang til fylke $accessValue");
        }

        // ARRANGEMENT TILGANG ELLER KOMMUNE
        if($accessType == 'arrangement') {
            if($accessValue == null) {
                if(AccessControlArrSys::hasArrangementAccess() === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til arrangementer", 401);
            }
            else {
                if(AccessControlArrSys::hasAccessToArrangement($accessValue) === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til arrangementet $accessValue");
            }
        }

        // Tilgang til arrangement eller kommuner/fylke arrangementet tilhører
        // Gjelder for arrangementer som tilhører en eller flere kommuner og 1 fylke. Fylkesadministratorer har tilgang til alle arrangementer i fylket, inkludering kommuner.
        if($accessType == 'arrangement_i_kommune_fylke') {
            if($accessValue == null) {
                throw new Exception("Mangler arrangement ID", 400);
            }
            else {
                $arrangement = null;

                try {
                    $arrangement = new Arrangement($accessValue);
                } catch(Exception $e) {
                    throw new Exception("Kunne ikke hente arrangementet med id $accessValue", 401);
                }

                if(AccessControlArrSys::hasAccessToArrangementOrKommmunerFylke($accessValue) === true) {
                    return true;
                }
            }
            throw new Exception("Du har ikke tilgang til arrangementet $accessValue. Du må være administrator i kommunen eller fylket som dette arrangementet tilhører", 401);
        }

        throw new Exception("Ukjent tilgangstype: $accessType");
        
    }

    
    /**
     * Retrieves a specific argument from a global request based on the provided key and method.
     * This method is intended to be used before the initialization process of the application context.
     *
     * @param string $key The name of the argument to retrieve.
     * @param string $method The HTTP method (e.g., GET, POST) through which the argument should be accessed.
     * @return mixed The value of the requested argument if found, or null if not found or if the request fails.
     */
    public static function getArgumentBeforeInit(string $key, string $method) {
        $request = Request::createFromGlobals();

        return $request->requestRequired($key, $method) ?? null;
    }

    /**
     * Sends an error message to the client.
     * 
     * This method sends an error message to the client with the specified message and HTTP status code.
     * This method can be used when the error occurs before the instance initialization of this class. 
     * 
     * @param string $message The error message to send to the client.
     * @param int $code The HTTP status code to send to the client.
     */
    public static function sendError(string $message, int $code) {
        $thisClass = new HandleAPICallWithAuthorization([], [], [], false, false);
        $thisClass->sendErrorToClient($message, $code);
    }

}