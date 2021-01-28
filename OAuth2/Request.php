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
    public function requestRequired($name) {
        $arg = parent::request($name, null);
        if($arg == null) {
            throw new Exception($name . ' finnes ikke!');
        }
        return $arg;
    }

    // Add request item to the list of items from the 
    public function addRequestItem($name, $value) {
        $this->request[$name] = $value;
    }

}