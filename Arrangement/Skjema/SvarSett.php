<?php

namespace UKMNorge\Arrangement\Skjema;

use UKMNorge\Database\SQL\Query;

require_once('UKM/Database/SQL/select.class.php');

class SvarSett {
    private $skjema = 0;
    private $fra = 0;
    private $svar = [];
    private $loaded = false;

    public static function createFromDatabase( Array $db_rows ) {
        #$svarsett = new SvarSett();
        
    }

    public function __construct( Int $skjema, Int $pl_id_fra )
    {
        $this->skjema = $skjema;
        $this->fra = $pl_id_fra;
    }

    /**
     * Hent skjema-ID
     * 
     * @return Int $skjema_id
     */ 
    public function getSkjemaId()
    {
        return $this->skjema;
    }

    /**
     * Hent eier av svar-settet
     * (Hvem har svart?)
     * 
     * @return Int $pl_id_fra
     */ 
    public function getFra()
    {
        return $this->fra;
    }

    /**
     * Get the value of svar
     * 
     * @return Array $svar for denne respondenten
     */ 
    public function getSvar()
    {
        if( !$this->_isLoaded() ) {
            $select = new Query(
                "SELECT *
                FROM `ukm_videresending_skjema_svar`
                WHERE `skjema` = '#skjema'
                AND `pl_fra` = '#fra'",
                [
                    'skjema' => $this->getSkjemaId(),
                    'fra' => $this->getFra()
                ]
            );

            $result = $select->run();

            while( $row = Query::fetch( $result ) ) {
                $this->svar[ $row['sporsmal'] ] = Svar::createFromDatabase( $row );
            }
            $this->loaded = true;
        }
        return $this->svar;
    }

    /**
     * Har vi noen som helst svar fra denne avsenderen?
     * 
     * @return Bool $har_svart
     */ 
    public function harSvart()
    {
        return sizeof( $this->getSvar() ) > 0;
    }

    /**
     * Get the value of loaded
     * 
     * @return Bool har lastet inn skjemadata
     */ 
    private function _isLoaded()
    {
        return $this->loaded;
    }
}