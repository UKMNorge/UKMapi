<?php

namespace UKMNorge\Geografi;

use UKMNorge\Database\SQL\Query;
require_once('UKM/Autoloader.php');

class Kommune {
    private $id = null;
    private $name = null;
    private $fylke = null;
    private $name_nonutf8 = null;
    private $aktiv = true;
    private $tidligere = null;
    private $tidligere_list = null;
    private $attributes = [];

	public function __construct( $kid_or_row ) {
		if( is_numeric( $kid_or_row ) ) {
			$this->_loadByID( $kid_or_row );
		} else {
			$this->_loadByRow( $kid_or_row );
		}
    }
    
    /**
     * Hent kommune-data fra database basert på gitt ID
     *
     * @param Int $id
     * @return $this
     */
	private function _loadByID( Int $id ) {
		$sql = new Query("SELECT *
						FROM `smartukm_kommune`
						WHERE `id` = '#id'",
						array('id' => $id ) );
		$res = $sql->run('array');
		if( !is_array( $res ) ) {
			$this->id = false;
		} else {
			$this->_loadByRow( $res );
        }
        return $this;
    }
    
    /**
     * Konverter database-rad til objekt
     *
     * @param Array $res
     * @return $this
     */
	private function _loadByRow( $res ) {
		$this->id = (int) $res['id'];
		$this->name = $res['name'];
		$this->fylke = Fylker::getById( $res['idfylke'] );
        $this->name_nonutf8 = $res['name'];
        $this->aktiv = ($res['active'] == 'true');
        $this->tidligere_list = $res['superseed'];

        // Hvis kommunen ikke har overtatt for noen, 
        // mellomlagre det, så slipper vi å beregne flere ganger
        if(empty($res['superseed'])) {
            $this->tidligere = false;
        }
        return $this;
	}
    
    /**
     * Hent ID
     *
     * @return Int $id
     */
	public function getId() {
		return $this->id;
	}
    
    /**
     * Hent navn
     *
     * @return String $navn
     */
	public function getNavn() {
		if( null == $this->name ) {
			return 'ukjent';
		}
		return $this->name;
    }
    
    /**
     * Hent navn for bruk i filter-lister (erstatt vanskelige tegn med enkle bokstaver)
     *
     * @return String enkelt navn
     */
    public function getFilterNavn() {
        return str_replace(
            ['ü','ä','à','á'],
            ['u','a','a','a'],
            $this->getNavn()
        );
    }
    
    /**
     * Hent navn uten UTF8 (deprecated)
     *
     * @return String $navn
     */
	public function getNavnUtenUTF8() {
		return $this->name_nonutf8;
	}
    
    /**
     * Hent foreldre-fylke
     *
     * @return fylke $fylke
     */
	public function getFylke() {
		return $this->fylke;
    }
    
    /**
     * Navnet er det viktigste
     *
     * @return string
     */
	public function __toString() {
		return $this->getNavn();
	}
    
    /**
     * URL-sanitizer (deprecated)
     *
     * @return String $urlsafe_name
     */
	public function getURLsafe() {
		$text = mb_strtolower( $this->getNavn() );
		$text = htmlentities($text);
	
		// eh, noen rare her, men muligens pga tidl dobbeltencode utf8
		$ut = array('&aring;','&aelig;','&oslash;','&atilde;','&ocedil;','&uuml;');
		$inn= array('a','a','o','o','o','u');
		$text = str_replace($ut, $inn, $text);
		
		$text = preg_replace("/[^A-Za-z0-9-]/","",$text);
		return $text;
	}

    /**
     * Er kommunen fortsatt aktiv, eller har den gått ut på dato?
     * 
     * @return Bool $aktiv
     */ 
    public function erAktiv()
    {
        return $this->aktiv;
    }

    /**
     * Er kommunen aktiv?
     * 
     * @alias erAktiv
     * @return Bool $aktiv
     */
    public function erAktivt() {
        return $this->erAktiv();
    }

    /**
     * Har kommunen overtatt for andre?
     *
     * @return Bool $har_overtatt
     */
    public function harTidligere() {
        return $this->tidligere !== false;
    }

    /**
     * Hvis kommunen har overtatt for andre,
     * last inn og returner disse
     * 
     * @return Array<Kommune> $inaktive kommuner
     */ 
    public function getTidligere()
    {
        if( $this->tidligere === false ) {
            return [];
        }

        if( $this->tidligere === null ) {
            $this->_loadTidligere();
        }
        return $this->tidligere;
    }

    /**
     * Hent ID-listen for tidligere arrangementer
     *
     * @return String CSV ID-liste
     */
    public function getTidligereIdList() {
        return $this->tidligere_list;
    }

    /**
     * Last inn kommuner denne kommunen har overtatt for
     *
     * @return void
     */
    private function _loadTidligere() {
        $this->tidligere = [];
        $sql = new Query(
            "SELECT *
            FROM `smartukm_kommune`
            WHERE `id` IN(#liste)",
            [
                'liste' => rtrim($this->tidligere_list,',')
            ]
        );
        $res = $sql->run();

        while( $row = SQL::fetch( $res ) ) {
            $this->tidligere[] = new Kommune( $row );
        }
    }

	/**
	 * Sett attributt
	 * Sett egenskaper som for enkelhets skyld kan følge mønstringen et lite stykke
	 * Vil aldri kunne lagres
	 *
	 * @param string $key
	 * @param $value
	 *
	 * @return innslag
	**/
	public function setAttr( $key, $value ) {
		$this->attributes[ $key ] = $value;
		return $this;
	}
	
	/**
	 * Hent attributt
	 *
	 * @param string $key
	 *
	 * @return value
	**/
	public function getAttr( $key ) {
		return isset( $this->attributes[ $key ] ) ? $this->attributes[ $key ] : false;
	}

        
    public static function validateClass( $object ) {
        return is_object( $object ) &&
            in_array( 
                get_class($object),
                ['UKMNorge\Geografi\Kommune','kommune']
            );
    }
}