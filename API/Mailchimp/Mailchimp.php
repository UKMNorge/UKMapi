<?php

namespace UKMNorge\API\Mailchimp;

require_once("UKM/Autoloader.php");
require_once("UKMconfig.inc.php");
require_once("UKM/curl.class.php");

use stdClass;
use Exception;
use UKMCURL;

/**
 *
 * Inneholder state-informasjon, f.eks "workflow running"?
 */
class Mailchimp
{

    private static $api_url;
    private static $api_key;
    private static $audiences = null;
    private static $pageSize = 500; // set den til 100 og bruk pagination (sjekk pagination lenke fra MailChimp)

    public static function init()
    {
        if (static::$api_key == null) {
            if (!defined("MAILCHIMP_API_KEY")) {
                throw new Exception("Missing MAILCHIMP_API_KEY");
            }
            static::$api_key = MAILCHIMP_API_KEY;
            static::$audiences = new Audiences();
        }
        return true;
    }

    /**
     * Sends a POST request to create new objects on the server
     * @param String $resource
     * @param Array $data
     * @return Result
     */
    public static function sendPostRequest(String $resource, array $data)
    {
        static::init();
        $curl = new UKMCURL();
        $curl->json($data);
        $curl->requestType("POST");
        $curl->user('userpwd:' . static::$api_key);

        return new Result($curl->request(static::_getUrl($resource)));
    }

    /**
     * Sends the request to the correct mailchimp server and parses the response, including any errors.
     * @param String $resource - lists, total_subscribers, ping etc
     * @param String $key for real result data from api
     * @return Result
     */
    public static function sendGetRequest(String $resource, $resource_key = null)
    {
        $offset = 0;
        $totalResults = 1;
        $data = [];
        $result = new stdClass;
        
        static::init();

        while($offset < $totalResults) {
            $url = static::_getUrl($resource);
            $url .= "?count=" . static::$pageSize . "&offset=" . $offset;
            $curl = new UKMCURL();
            $curl->requestType("GET");
            $curl->user('userpwd:' . static::$api_key);
            
            $result = $curl->request($url);
            
            $totalResults = $result->total_items;
            $offset += static::$pageSize;
            
            if( isset($result->$resource_key) && is_array($result->$resource_key) ) {
                $data = array_merge( $data, $result->$resource_key);
            }
        }
        
        # Kind of merging the data from all requests
        # into the last performed request, faking one 
        # huge result.
        $result->$resource_key = $data;
        
        return new Result($result);
    }

    /**
     * Ping the api (just for fun? or debug)
     *
     * @return boolean
     */
    public function ping(): bool
    {
        $result = static::sendGetRequest("ping");

        return is_object($result->getData());
    }

    /**
     * Set the value of pageSize
     * 
     * Used when page is set in request parameters
     *
     * @param Int $pageSize
     * @return void
     */
    public static function setPageSize(Int $pageSize)
    {
        static::$pageSize = $pageSize;
    }

    /**
     * Fetch a collection of audiences
     *
     * @return Audiences
     */
    public static function getAudiences() {
        if( null == static::$audiences ) {
            static::$audiences = new Audiences();
        }
        return static::$audiences;
    }

    /**
     * Get API-url from resource
     *
     * @param String $resource
     * @return String $url
     */
    private static function _getUrl(String $resource)
    {
        if (null == static::$api_url) {
            static::$api_url = 'https://' .
                substr(
                    static::$api_key,
                    strrpos(static::$api_key, '-') + 1
                ) .
                '.api.mailchimp.com/3.0';
        }
        return rtrim(static::$api_url, '/') . "/" . $resource;
    }

}