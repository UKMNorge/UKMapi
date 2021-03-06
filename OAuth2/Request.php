<?php

namespace UKMNorge\OAuth2;

use Exception;
use OAuth2\Request as BshafferRequest;

class Request extends BshafferRequest {
    
    /**
     * @param string $name
     * @param mixed  $default
     * @return mixed
     */
    public function requestRequired($name, $method = 'POST') { // REMOVE POST from method (required)
        $arg = $method == 'POST' ? parent::request($name, null) : $arg = parent::query($name);

        if($arg == null) {
            throw new Exception('Argumentet ' . $name . ' finnes ikke!');
        }
        return $arg;
    }

    // Add request item to the list of items from the 
    public function addRequestItem($name, $value) {
        $this->request[$name] = $value;
    }

    /**
     * Bare for VS CODE skyld
     * 
     * @return Request
     */
    public static function createFromGlobals() {
        return parent::createFromGlobals();
    }


}