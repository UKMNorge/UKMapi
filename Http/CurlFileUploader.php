<?php

namespace UKMNorge\Http;

use CURLFile;

class CurlFileUploader extends Curl
{
    /**
     * Create file uploader object
     * 
     * @param $filePath absolute path of file
     * @param $formFileVariableName form field name to upload file
     * @param $otherParams assosiative array of other params which you want to send as post
     */
    public function __construct(String $filePath, String $formFileVariableName, array $parameters = null)
    {
        $post_values = is_array($parameters) ? $parameters : [];
        $post_values[$formFileVariableName] = new CURLFile(realpath($filePath));
        $this->post($post_values);
    }
}