<?php

namespace UKMNorge\Http;

class Curl
{

    var $timeout = 15;
    var $headers = false;
    var $content = true;
    var $postdata = false;
    var $requestType = "GET";
    var $json = false;
    var $headerList = array();
    var $error;
    var $user = false;
    var $encoding;

    public function __construct()
    { }

    public function timeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    public function port($port)
    {
        $this->port = $port;
        return $this;
    }

    // Sets --user-option of curl to support HTTP Basic Authentication.
    public function user($userString)
    {
        $this->user = $userString;
    }

    public function post($postdata)
    {
        $this->postdata = $postdata;
        $this->requestType('POST');
        return $this;
    }

    // To be used if request type is different than GET or POST
    public function requestType($reqType)
    {
        $this->requestType = $reqType;
    }

    public function addHeader($header)
    {
        $this->headerList[] = $header;
    }

    public function error()
    {
        return $this->error;
    }
    public function headersOnly()
    {
        $this->headers = true;
        $this->content = false;
        $this->timeout(2);
        return $this;
    }

    public function process($url)
    {
        return $this->request($url);
    }

    public function json($object)
    {
        $this->json = true;
        $this->json_data = json_encode($object);
        return $this;
    }

    public function setEncoding( String $encoding ) {
        $this->encoding = $encoding;
        return $this;
    }

    public function request($url)
    {
        $this->url = $url;

        //curl_setopt($this->curl, CURLOPT_VERBOSE, true);

        $this->_init();

        // Is this a post-request?
        if ($this->postdata) {
            curl_setopt($this->curl, CURLOPT_POST, true);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->postdata);
        } else {
            curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $this->requestType);
        }

        // Get only headers
        if (!$this->content) {
            curl_setopt($this->curl, CURLOPT_HEADER, 1);
            curl_setopt($this->curl, CURLOPT_NOBODY, 1);
        }

        if ($this->user) {
            curl_setopt($this->curl, CURLOPT_USERPWD, $this->user);
        }

        // Set extra headers	
        if (!empty($this->headerList)) {
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headerList);
        }

        if( !is_null($this->encoding)) {
            curl_setopt($this->curl, CURLOPT_ENCODING, $this->encoding);
        }

        if ($this->json) {
            if ($this->requestType == "GET") {
                // Hvis request-type ikke er endret fra standard
                curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, "POST");
            }
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->json_data);
            #curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

            // Force headers to be json
            $this->headerList[] = 'Content-type: application/json; charset=utf-8';
            $this->headerList[] = 'Content-Length: ' . strlen($this->json_data);
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headerList);
        }

        // Use custom port
        if (isset($this->port)) {
            curl_setopt($this->curl, CURLOPT_PORT, $this->port);
        }
        //var_dump($this);
        // Execute
        $this->result = curl_exec($this->curl);
        if ($this->result == false) {
            $this->error = curl_error($this->curl);
        }
        // Default, return content of processed request
        if ($this->content) {
            $this->_analyze();
            // Return only header infos
        } else {
            $info = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
            curl_close($this->curl);
            return $info;
        }

        // Close connection
        curl_close($this->curl);

        return $this->data;
    }

    /**
     * Hent response-objekt fra forespørselen
     *
     * @return any
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Hent (raw) response fra forespørselen
     *
     * @return String 
     */
    public function getResult() {
        return $this->result;
    }

    private function _init()
    {
        $this->curl = curl_init();
            curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_REFERER, $_SERVER['PHP_SELF']);
        curl_setopt($this->curl, CURLOPT_USERAGENT, "UKMNorge API");
        curl_setopt($this->curl, CURLOPT_HEADER, $this->headers);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, false); // WHOA, PLEASE DON'T
    }

    private function _analyze()
    {
        $this->_isJson();
        $this->_isSerialized();
        if (!isset($this->data)) {
            if ($this->result === 'false')
                $this->data = false;
            elseif ($this->result === 'true')
                $this->data = true;
            else
                $this->data = $this->result;
        }
    }

    private function _isJson()
    {
        $decoded = @json_decode($this->result);
        $this->is_json = is_array($decoded) || is_object($decoded);

        if ($this->is_json)
            $this->data = $decoded;
    }

    private function _isSerialized()
    {
        $data = @unserialize($this->result);
        if ($this->result === 'b:0;' || $data !== false) {
            $this->data = $data;
        }
    }
}
