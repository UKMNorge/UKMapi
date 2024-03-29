<?php

namespace UKMNorge\Sensitivt;
use Exception;
use UKMNorge\Allergener\Allergener;

/**
 * 
 * DEVELOPER: SENSITIVT-KLASSER SKAL ALDRI
 * KJØRE SQL-SPØRRINGER DIREKTE, MEN ALLTID BRUKE
 * self::query( $sql, $data )
 * 
 */

class Intoleranse extends Sensitivt {    
    const DB_TABLE = 'ukm_sensitivt_intoleranse';
    const DB_ID = 'p_id';

    protected $har = null;
    protected $tekst = null;
	protected $_liste = null;
	protected $intoleranser = null;

    /**
     * Hent inn en intoleranse
     *
     * @param Int $id
     */
    public function __construct( $id ) {
        parent::__construct( $id );
        $this->_load( $id );
    }

	/**
	 * Load from database
	 *
	 * @param Int $id
	 * @return void
	 */
    protected function _load( $id ) {
        $res = self::query("
            SELECT * 
            FROM `#db_table`
            WHERE `#db_id` = '#id' and leder_id is NULL",
            [
                'id' => $id,
                'db_id' => static::DB_ID,
                'db_table' => static::DB_TABLE
            ]
        );

		$this->_liste = [];
		$this->intoleranser = [];

        if( !$res ) {
            $this->har = false;
            return false;
        }

        $this->_populate( self::getFirstRow( $res ) );
    }

	/**
	 * Populate data from database
	 *
	 * @param [type] $data
	 * @return void
	 */
    protected function _populate( $data ) {
        if( null == $data ) {
            $this->har = false;
            return false;
        }

        $this->har = true;
		$this->tekst = $data['tekst'];
		$this->setListe( $data['liste'] );
    }

	/**
	 * Hvorvidt personen har en registrert intoleranse
	 *
	 * @return Bool
	 */
	public function har() {
		if( $this->har == null ) {
			$this->har = !empty( $this->getTekst() ) || !empty( $this->getListeHuman() );
		}
        return $this->har;
	}

    /**
     * Har personen dette allergenet?
     *
     * @param String $id
     * @return void
     */
	public function harDenne( $id ) {
		return in_array( $id, $this->getListe() );
	}


	/**
	 * Sett tekst
	 *
	 * @param String $tekst
	 * @return void
	 */
	public function setTekst( String $tekst ) {
		$this->tekst = $tekst;
		return $this;
	}

	/**
	 * Hent beskrivelse / tekst
	 *
	 * @return String
	 */
    public function getTekst() {
        return $this->tekst;
	}

	/**
	 * Set ny liste med intoleranser
	 * Trigger re-load av human-liste og intoleranse-objekt
	 *
	 * @param String|Array $liste
	 * @return void
	 */
	public function setListe( $liste ) {
		$this->intoleranser = null;
		$this->liste_human = null;

		if( is_string( $liste ) ) {
			$this->_liste = strlen( $liste ) > 0 ? explode('|', $liste) : [];
		} elseif( is_array( $liste ) ) {
			$this->_liste = $liste;
		} elseif( is_null( $liste ) ) {
			$this->_liste = [];
		} else {
			throw new Exception('Beklager, gitt liste må være array eller string');
		}
	}

	/**
	 * Hent liste
	 * 
	 * @return Array $liste
	 */
	public function getListe() {
		return $this->_liste;
	}

	/**
	 * Hent string-representasjon av listen (human)
	 *
     * @param Bool Skjul varsel-trekant på slutten av listen
	 * @return String csv-liste
	 */
	public function getListeHuman(Bool $skipWarning=false) {
		if( null == $this->liste_human ) {
			$this->getIntoleranser($skipWarning);
		}
		return $this->liste_human;
	}
	
	/**
	 * Hent alle intoleranse-objekt
	 *
     * @param Bool Skjul varsel-trekant på slutten av listen
	 * @return Array<Intoleranse>
	 */
	public function getIntoleranser(Bool $skipWarning=false) {
		if( null == $this->intoleranser && $this->getListe()) {
			$intoleranser = [];
			$human = '';

			if( !is_array( $this->getListe() ) ) {
				debug_print_backtrace();
				die();
			}
			foreach( $this->getListe() as $id ) {
				$intoleranse = Allergener::getById( $id );
				$intoleranser[] = $intoleranse;
				$human .= $intoleranse->getNavn() .', ';
			}			
			$human = rtrim($human, ', ');

			if( !empty( $this->getTekst() ) && !$skipWarning ) {
				$human .= ' ⚠ ';
			}
			
			$this->intoleranser = $intoleranser;
			$this->liste_human = $human;
		}
		return $this->intoleranser;
	}
}