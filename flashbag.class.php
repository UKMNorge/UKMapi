<?php

class UKMflash {
    private $id = null;

    /**
     * Opprett ny flashbag
     * 
     * @param string unik ID for flashbag
     */
    public function __construct( $id ) {
        $this->id = $id;
    }

    /**
     * Legg til en melding i flashbag
     * 
     * @param string $level (danger,warning,info,success osv ref bootstrap)
     * @param string $message
     * @return this
     */
    public function add( $level, $message ) {
        $_SESSION[ $this->_sessid() ][] = [
            'level' => $level, 
            'text' => $message,
        ];

        return $this;
    }
    
    /**
     * Hent alle meldinger i flashbag 
     * OBS: Slettes automatisk etter henting
     * 
     * @return array $messages
     */
    public function getAll() {
        $messages = [];
        if( is_array( $_SESSION[$this->_sessid()] ) ) {
            foreach( $_SESSION[$this->_sessid()] as $message ) {
                $messages[] = $message;
                unset( $_SESSION[$this->_sessid()] );
            }
        }
        return $messages;
    }

    /**
     * Need to speak - har flashbag meldinger?
     * ALIAS: self::has()
     * 
     * @return bool
     */
    public function needToSpeak() {
        return self::has();
    }

    /**
     * Has - har flashbag meldinger?
     * 
     * @return bool
     */
    public function has() {
        return isset( $_SESSION[ $this->_sessid() ] ) && 
            is_array( $_SESSION[ $this->_sessid() ] ) &&
            sizeof( $_SESSION[ $this->_sessid() ] ) > 0;
    }

    /**
     * Hent messages-array fra session for manipulering
     * 
     * @return $_SESSION[ $flashbag_id ]
     */
    private function _messages() {
        return $_SESSION[ $this->_sessid() ];
    }

    /**
     * Hent current flashbag ID
     */
    private function _sessid() {
        return 'UKMflash_'. $this->id;
	}

}