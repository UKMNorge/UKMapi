<?php

namespace UKMNorge\Tools;


use Exception;

require_once('UKM/Autoloader.php');

class Sanitizer {

    /**
     * Sanitize string
     *
     * @param string $navn
     * @return string
     **/
    public static function sanitizeString(string $etternavn) {
        return static::removeEmoji($etternavn);
    }
    
    /**
     * Sanitize navn
     *
     * @param string $navn
     * @return string
     **/
    public static function sanitizeNavn(string $navn) {
        return static::removeEmoji(stripslashes($navn));
    }

    /**
     * Sanitize etternavn
     *
     * @param string $navn
     * @return string
     **/
    public static function sanitizeEtternavn(string $etternavn) {
        return static::removeEmoji($etternavn);
    }

    /**
     * Fjern emoji fra string
     *
     * @param string $string
     * @return string
     **/
    private static function removeEmoji($string) {

        // Match Emoticons
        $regex_emoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clear_string = preg_replace($regex_emoticons, '', $string);
    
        // Match Miscellaneous Symbols and Pictographs
        $regex_symbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clear_string = preg_replace($regex_symbols, '', $clear_string);
    
        // Match Transport And Map Symbols
        $regex_transport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clear_string = preg_replace($regex_transport, '', $clear_string);
    
        // Match Miscellaneous Symbols
        $regex_misc = '/[\x{2600}-\x{26FF}]/u';
        $clear_string = preg_replace($regex_misc, '', $clear_string);
    
        // Match Dingbats
        $regex_dingbats = '/[\x{2700}-\x{27BF}]/u';
        $clear_string = preg_replace($regex_dingbats, '', $clear_string);
    
        return $clear_string;
    }
}