<?php

namespace UKMNorge\Http;

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
        if (is_array($parameters)) {
            foreach ($parameters as $post_name => $value) {
                $post_values[$post_name] = $value;
            }
        } else {
            $post_values = [];
        }
        $post_values[$formFileVariableName] = "@" . $filePath;

        $this->post($post_values);
        $this->addHeader('Expect:');
    }
}