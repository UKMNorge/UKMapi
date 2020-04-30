<?php

namespace UKMNorge\Slack\Exceptions;

/**
 * App initiation errors
 * 
 */
class InitException extends \Exception {
    
    public function __construct(String $type)
    {
        switch( $type ) {
            case 'token':
                parent::__construct('App initFromToken() must be run before getToken()');
            break;
            case 'signingsecret':
            case 'idsecret':
                parent::__construct('App initFromAppDetails() must be run before getToken()');
            break;
            default:
                parent::__construct('Unknown app Id exception occured');
            break;
        }
    }
}