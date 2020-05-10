<?php

namespace UKMNorge\Slack\API\Response\Plugin\Transport;

use UKMNorge\Slack\API\Response\View;

class ViewSubmission extends Transport implements TransportInterface {
    
    public $view;

    public function _post_construct() {
        $this->setId( $this->getData()->trigger_id );
        $this->setView( 
            new View(
                $this->getData()->view->id,
                $this->getData()->view->callback_id
            )
        );
    }

    public function setView( View &$view ) {
        $this->view = $view;
    }

    public function getView() {
        return $this->view;
    }
}