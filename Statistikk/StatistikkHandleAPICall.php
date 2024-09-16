<?php

namespace UKMNorge\Statistikk;

use UKMNorge\OAuth2\HandleAPICall;
use UKMNorge\Statistikk\StatistikkManager;
use UKMNorge\OAuth2\Request;
use Exception;
use UKMNorge\Arrangement\Arrangement;

class StatistikkHandleAPICall extends HandleAPICall {
    private $accessType;
    private $accessValues;


    /**
     * Constructs a new instance of the API call handler for statistics.
     *
     * This constructor initializes the API call handler with required and optional arguments,
     * accepted HTTP methods, and login requirements. It also performs an access check to ensure
     * the user has the necessary permissions to view the requested statistics. If the user does
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
        
        // VIKTIG: Sjekk at bruker har tilgang til å se statistikken
        try{
            if($this->verifyAccess($accessType, $accessValue) !== true) {
                $this->sendErrorToClient('Tilgangen er ikke gitt', 401);

            }
        }catch(Exception $e) {
            $this->sendErrorToClient($e->getMessage(), 401);
        }
    }


    /**
     * Verifies if the current user has access to the requested statistical data.
     * 
     * IMPORTANT: This method should be called before any other code in the API call.
     * IMPORTANT2: If the access type is not defined, access is granted.
     * 
     * This method checks if the user is a super admin or has the required access level
     * (fylke, kommune or arrangement) to view specific statistical data. If the user does not
     * have the necessary permissions, an exception is thrown.
     * 
     * @param string $accessType The type of access required ('fylke', 'kommune' or 'arrangement').
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
                if(StatistikkManager::hasFylkeAccess() === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til fylker for å se denne statistikken");
            }
            // Tilgang til spesifikt fylke
            else {
                if(StatistikkManager::hasAccessToFylke($accessValue) === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til fylket for å se denne statistikken");
            }
        }

        // KOMMUNE TILGANG
        if($accessType == 'kommune') {
            if($accessValue == null) {
                if(StatistikkManager::hasKommuneAccess() === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til kommuner for å se denne statistikken", 401);
            }
            else {
                if(StatistikkManager::hasAccessToKommune($accessValue) === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til kommune $accessValue for å se denne statistikken");
            }
        }

        // Har tilgang i en kommune som er del av dette ($accessValue) fylke
        // Hvis brukerne har tilgang direkte til fylke, så har de tilgang til alle kommuner i fylket
        if($accessType == 'fylke_fra_kommune') {
            // Har tilgang direkt til fylke
            if(StatistikkManager::hasAccessToFylke($accessValue) === true) {
                return true;
            }
            // Har tilgang til fylke fra kommune
            if(StatistikkManager::hasAccessToFylkeFromKommune($accessValue) === true) {
                return true;
            }
            throw new Exception("Du har ikke tilgang til fylke $accessValue for å se denne statistikken");
        }

        // ARRANGEMENT TILGANG ELLER KOMMUNE
        if($accessType == 'arrangement') {
            if($accessValue == null) {
                if(StatistikkManager::hasArrangementAccess() === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til arrangementer for å se denne statistikken", 401);
            }
            else {
                if(StatistikkManager::hasAccessToArrangement($accessValue) === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til arrangement for å se denne statistikken");
            }
        }

        // Tilgang til arrangement eller kommune/fylke arrangementet tilhører
        // Gjelder for arrangementer som tilhører en kommune eller fylke. Fylkesadministratorer har tilgang til alle arrangementer i fylket, inkludering kommuner.
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

                if(StatistikkManager::hasAccessToArrangement($accessValue) === true) {
                    return true;
                }

                // Sjekk kommuner
                $kommuner = $arrangement->getKommuner();
                foreach($kommuner as $kommune) {
                    if(StatistikkManager::hasAccessToKommune($kommune->getId()) === true) {
                        return true;
                    }
                }

                // Sjekk fylke
                $fylke = $arrangement->getFylke();
                if(StatistikkManager::hasAccessToFylke($fylke->getId()) === true) {
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

    public static function sendError(string $message, int $code) {
        $thisClass = new StatistikkHandleAPICall([], [], [], false, false);
        $thisClass->sendErrorToClient($message, $code);
    }

}