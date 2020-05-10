<?php

namespace UKMNorge\Slack\Payload;

use stdClass;
use UKMNorge\Slack\Block\Structure\Exception;

class Home extends Payload
{
    const TYPE = 'home';
    const MAX_PRIVATE_METADATA_LENGTH = 3000;
    const MAX_CALLBACK_ID_LENGTH = 255;

    public $private_metadata;
    public $callback_id;
    public $external_id;

    /**
     * Set private metadata
     *
     * @param array $metadata
     * @return self
     */
    public function setPrivateMetadata(array $metadata)
    {
        $encoded = json_encode($metadata);
        if (strlen($encoded) > static::MAX_PRIVATE_METADATA_LENGTH) {
            throw new Exception(
                'Private metadata must be less than ' . static::MAX_PRIVATE_METADATA_LENGTH . ' when json_encoded. ' .
                    'Currently at ' . strlen($encoded) . ' characters.',
                'maxlength_private_metadata'
            );
        }
        $this->private_metadata = $metadata;
    }

    /**
     * Get private metadata
     *
     * @return String
     */
    public function getPrivateMetadata() {
        return $this->private_metadata;
    }

    /**
     * Set callback Id
     *
     * @param String $callback_id
     * @return self
     */
    public function setCallbackId( String $callback_id ) {
        if( strlen($callback_id) > static::MAX_CALLBACK_ID_LENGTH ) {
            throw new Exception(
                'Callback id must be less than '. static::MAX_CALLBACK_ID_LENGTH .' characters. '. strlen($callback_id) .' given',
                'maxlength_callback_id'
            );
        }

        $this->callback_id = $callback_id;
        return $this;
    }

    /**
     * Get callback id
     *
     * @return String
     */
    public function getCallbackId() {
        return $this->callback_id;
    }

    /**
     * Set external id
     *
     * @param String $external_id
     * @return self
     */
    public function setExternalId(String $external_id) {
        $this->external_id = $external_id;
        return $this;
    }

    /**
     * Get external id
     *
     * @return String
     */
    public function getExternalId() {
        return $this->external_id;
    }

    /**
     * Export data
     * 
     * TODO: check if json_encoding here causes trouble later
     *
     * @return stdClass
     */
    public function export() {
        $data = parent::export();

        if( !is_null( $this->getPrivateMetadata() ) ) {
            $data->private_metadata = json_encode($this->getPrivateMetadata());
        }

        if( !is_null( $this->getCallbackId())) {
            $data->callback_id = Payload::convert($this->getCallbackId());
        }

        if( !is_null( $this->getExternalId())) {
            $data->external_id = Payload::convert($this->getExternalId());
        }

        return $data;
    }
}
