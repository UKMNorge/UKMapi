<?php

namespace UKMNorge\Slack\Block\Element;

use stdClass;
use UKMNorge\Slack\Block\Structure\Element;
use UKMNorge\Slack\Block\Structure\Exception;
use UKMNorge\Slack\Payload\Payload;

class Image extends Element
{

    const TYPE = 'image';
    const REQUIRE_ACTION_ID = true;

    const MAX_URL_LENGTH = 3000;
    const MAX_ALT_TEXT_LENGTH = 2000;

    public $url;
    public $alt_text;

    public function __construct(String $image_url, String $alt_text)
    {
        $this->setUrl($image_url);
        $this->setAltText($alt_text);
    }


    /**
     * Set image url
     *
     * @param String $image_url
     * @return self
     */
    public function setUrl(String $image_url)
    {
        if (strlen($image_url) > static::MAX_URL_LENGTH) {
            throw new Exception(
                'Image url maxlength is ' . static::MAX_URL_LENGTH . '. Given image url length is ' . strlen($image_url),
                'maxlength_image_url'
            );
        }
        $this->url = $image_url;
        return $this;
    }

    /**
     * Get image url
     *
     * @return String
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set image alt text
     *
     * @param String $alt_text
     * @return self
     */
    public function setAltText(String $alt_text)
    {
        if (strlen($alt_text) > static::MAX_ALT_TEXT_LENGTH) {
            throw new Exception(
                'Image alt text maxlength is ' . static::MAX_ALT_TEXT_LENGTH . '. Given alt text lenght is ' . strlen($alt_text),
                'maxlength_image_alt_text'
            );
        }
        $this->alt_text = $alt_text;
        return $this;
    }

    /**
     * Get image alt text
     *
     * @return String
     */
    public function getAltText()
    {
        return $this->alt_text;
    }

    /**
     * Set confirm not supported!
     *
     * @throws Exception
     */
    public function setConfirm()
    {
        throw new Exception('Confirm is not supported', 'not_supported_confirm');
    }

    /**
     * Get export data
     *
     * @return stdClass
     */
    public function export()
    {
        $data = parent::export(); // type + action_id (if not null) + confirm (if not null)

        // Placeholder
        if (is_null($this->getUrl())) {
            throw new Exception('Image element missing required url', 'missing_url');
        }
        $data->url = Payload::convert($this->getUrl());

        if (is_null($this->getAltText())) {
            throw new Exception('Image element missing required alt text', 'missing_alt_text');
        }
        $data->alt_text = Payload::convert($this->getAltText());

        return $data;
    }
}
