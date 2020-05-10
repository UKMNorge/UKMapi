<?php
	
namespace UKMNorge\Slack\API;

use stdClass;
use UKMNorge\Slack\Exceptions\SetupException;

class View {

    private $trigger_id;
    private $view;

    private $hash;
    private $view_id;
    private $external_id;
    private $user_id;

    public function __construct( String $trigger_id = null, stdClass $view ) {
        $this->trigger_id = $trigger_id;
        $this->view = $view;
    }

    public function setHash( String $hash ) {
        $this->hash = $hash;
        return $this;
    }

    public function setViewId( String $view_id ) {
        $this->view_id = $view_id;
        return $this;
    }

    public function setExternalId( String $external_id ) {
        if( strlen($external_id) > 255 ) {
            throw new SetupException('External id must be less than 255 characters');
        }
        $this->external_id = $external_id;
        return $this;
    }

    public function setUserId( String $user_id ) {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * Open a view
     *
     * @return stdClass from slack api
     */
     public function open() {
         $this->requireTriggerId();
         $this->requireViewTypeModal('open');
         
         $data = [
            'view' => $this->view,
            'trigger_id' => $this->trigger_id
        ];
         
         return App::botPost('views.open', $data);
        }
        
    /**
     * Update a view
     *
     * @return stdClass from slack api
     */
    public function update() {
        $this->requireViewTypeModal('update');
        if( is_null($this->view_id) && is_null($this->external_id) ) {
            throw new SetupException('View update requires either view_id or external_id');
        }
        
        $data = [
            'view' => $this->view
        ];
        
        if( !is_null($this->view_id) ) {
            $data['view_id'] = $this->view_id;
        }
        if( !is_null($this->external_id)) {
            $data['external_id'] = $this->external_id;
        }
        if( !is_null($this->hash)) {
            $data['hash'] = $this->hash;
        }
        
        return App::botPost('views.update', $data);
    }
    
    /**
     * Push a view
     *
     * @return stdClass from slack api
     */
    public function push() {
        $this->requireTriggerId();
        $this->requireViewTypeModal('push');

        $data = [
            'view' => $this->view,
            'trigger_id' => $this->trigger_id
        ];
        
        return App::botPost('views.push', $data);
    }

    /**
     * Open app homepage for a user
     *
     * @return stdClass from slack api
     */
    public function publish() {
        $this->requireViewTypeHome('publish');
        if( is_null($this->user_id)) {
            throw new SetupException('Cannot open home page without user id');
        }
        
        $data = [
            'user_id' => $this->user_id,
            'view' => $this->view
        ];

        if( !is_null($this->hash)) {
            $data->hash = $this->hash;
        }

        return App::botPost('views.publish', $data);
    }



    private function requireTriggerId() {
        if( is_null($this->trigger_id ) ) {
            throw new SetupException('Cannot open modal without trigger id');
        }
    }

    private function requireViewTypeModal($function) {
        if( $this->view->type != 'modal') {
            throw new SetupException( $function . ' requires a modal-view attachment');
        }
    }

    private function requireViewTypeHome($function) {
        if( $this->view->type != 'home') {
            throw new SetupException( $function . ' requires a home-view attachment');
        }
    }

}