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
        $_SESSION['UKMmodul_messages'][] = [
            'level' => $level, 
            'message' => $message,
        ];

        return $this;
    }
    
    /**
     * Hent alle meldinger i flashbag 
     * OBS: Slettes automatisk etter henting
     * 
     * @return array $messages
     */
    public static function getAll() {
        $messages = [];
        if( is_array( $_SESSION['UKMmodul_messages'] ) ) {
            foreach( $_SESSION['UKMmodul_messages'] as $message ) {
                $messages[] = $message;
                unset( $_SESSION['UKMmodul_messages'] );
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
    public static function needToSpeak() {
        return self::has();
    }

    /**
     * Has - har flashbag meldinger?
     * 
     * @return bool
     */
    public static function has() {
        return isset( $_SESSION[ $this->_sessid() ] ) && 
            is_array( $_SESSION['UKMmodul_messages'] ) &&
            sizeof( $_SESSION['UKMmodul_messages'] ) > 0;
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
        return $_SESSION['UKMflash_'. $this->id];
    }
}