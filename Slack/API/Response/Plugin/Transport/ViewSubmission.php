<?php

namespace UKMNorge\Slack\API\Response\Plugin\Transport;

use UKMNorge\Slack\API\Response\View;

class ViewSubmission extends Transport implements TransportInterface
{

    public $view;
    public $metadata;

    public function _post_construct()
    {
        $this->setId($this->getData()->trigger_id);
        $this->setView(
            new View(
                $this->getData()->view->id,
                $this->getData()->view->callback_id
            )
        );
    }

    public function setView(View &$view)
    {
        $this->view = $view;
    }

    public function getView()
    {
        return $this->view;
    }

    public function getCallbackId()
    {
        return $this->getView()->getCallbackId();
    }

    /**
     * Fetch one or all metadata value(s)
     *
     * @param String $key
     * @return mixed
     */
    public function getMetadata(String $key = null)
    {
        if (is_null($this->metadata)) {
            $this->metadata = json_decode($this->getData()->view->private_metadata);
        }

        if (is_null($key)) {
            return $this->metadata;
        }

        if (!isset($this->metadata->$key)) {
            return false;
        }
        return $this->metadata->$key;
    }
}
