<?php

namespace UKMNorge\Slack;

class Log {
    const DEBUG = true;

    public static function log( String $message ) {
        if( static::DEBUG ) {
            error_log($message);
        }
    }
}