<?php

namespace UKMNorge\Statistikk;

use UKMNorge\OAuth2\HandleAPICall;
use UKMNorge\Statistikk\StatistikkManager;
use Exception;

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
     * @param string|null $accessValue Additional values required for access verification (e.g., fylke ID). The values are just the names of the values, not the actual values. Actual values are stored at requiredArguments.
     */
    function __construct(array $requiredArguments, array $optionalArguments, array $acceptedMethods, bool $loginRequired, bool $wordpressLogin = false, string $accessType = null, string $accessValue = null) {
        parent::__construct($requiredArguments, $optionalArguments, $acceptedMethods, $loginRequired, $wordpressLogin);
        
        // VIKTIG: Sjekk at bruker har tilgang til å se statistikken
        try{
            $this->verifyAccess($accessType, $accessValue);
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
        if(is_super_admin()) {
            return true;
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
                if(StatistikkManager::hasAccessToFylke($this->getArgument($accessValue)) === true) {
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
                if(StatistikkManager::hasAccessToKommune($this->getArgument($accessValue)) === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til kommunen for å se denne statistikken");
            }
        }

        // ARRANGEMENT TILGANG
        if($accessType == 'arrangement') {
            if($accessValue == null) {
                if(StatistikkManager::hasArrangementAccess() === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til arrangementer for å se denne statistikken", 401);
            }
            else {
                if(StatistikkManager::hasAccessToArrangement($this->getArgument($accessValue)) === true) {
                    return true;
                }
                throw new Exception("Du har ikke tilgang til arrangement for å se denne statistikken");
            }
        }

        throw new Exception("Ukjent tilgangstype: $accessType");
        
    }

}