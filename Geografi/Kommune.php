<?php

namespace UKMNorge\Geografi;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Nettverk\Omrade;

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
    private $overtatt;
    private $overtatt_av;
    private $omrade = null;
    private $path = null;
    private $modifiedPath = null;

	public function __construct( $kid_or_row ) {
		if( is_numeric( $kid_or_row ) ) {
			$this->_loadByID( $kid_or_row );
		} elseif(!empty($kid_or_row)) {
			$this->_loadByRow( $kid_or_row );
		}
    }

    public static function getLoadQuery() {
        return "SELECT *,
            (
                SELECT `id` 
                FROM `smartukm_kommune` AS `overtatt` 
                WHERE FIND_IN_SET(`smartukm_kommune`.`id`, `overtatt`.`superseed`)
            ) AS `overtatt_av`
            FROM `smartukm_kommune`";
    }
    
    /**
     * Hent kommune-data fra database basert på gitt ID
     *
     * @param Int $id
     * @return $this
     */
	private function _loadByID( Int $id ) {
		$sql = new Query(
            static::getLoadQuery()."
            WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );
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
		$this->fylke = Fylker::getById( (Int)$res['idfylke'] );
        $this->name_nonutf8 = $res['name'];
        $this->aktiv = ($res['active'] == 'true');
        $this->tidligere_list = $res['superseed'];
        $this->overtatt = !is_null( $res['overtatt_av'] );
        $this->overtatt_av = (Int) $res['overtatt_av'];

        if( isset( $res['path'] ) && !empty( $res['path'] ) ) {
            $this->path = $res['path'];
        }

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
     * Hent path for kommunen
     *
     * @return String $path
     */
    public function getPath() {
        $modifiedPath = $this->hasModifiedPath();
        if($modifiedPath != null) {
            $this->modifiedPath = $modifiedPath;
            return $this->modifiedPath;
        }

        if( null == $this->path ) {
            $this->path = '/' . static::sanitizePath(
                explode(
                    '(', 
                    $this->getNavn()
                )[0]
            ) .'/';
        }
        return $this->path;
    }


    /**
     * Hent kommune modifisert path.
     * Modifisert path brukes når kommune skal ha en annen path enn det som genereres fra kommunes navn.
     * I tillegg brukes modifiserte path for å gjøre mulig å ha felles nettside for flere kommuner
     *
     * @return String|null $path
     */
    private function hasModifiedPath() {
        $path = null;
        
        $sql = new Query(
            "SELECT path from `ukm_kommune_path`
            WHERE `kommune_id` = '#k_id'",
            [
                'k_id' => $this->id,
            ]
        );
		$res = $sql->run('array');
        if( $res ) {
            $path = $res['path'];
    
            return $path ? $path : null;
        }
        return null;
    }

    /**
     * Hent modifisert path for kommune
     *
     * @return String $modifiedPath
     */
    public function getModifiedPath() {
        if(!$this->modifiedPath) {
            $this->modifiedPath = $this->hasModifiedPath();
        }
        return $this->modifiedPath;
    }

    /**
     * Hent kommuner som bruker same path (modifisert path)
     *
     * @return Array<Kommune> kommuner som bruker modifisert path sammen med denne kommunen
     */
    public function getKommunerOnSamePath() {
        $kommuner = [];

        $sql = new Query(
            "SELECT kommune_id from `ukm_kommune_path`
            WHERE `path` = '#path'",
            [
                'path' => $this->getModifiedPath(),
            ]
        );
		$res = $sql->run();
        while($row = Query::fetch($res)) {
            $k_id = $row['kommune_id'];
            if($k_id != $this->id) {
                $kommuner[] = new Kommune($k_id);
            }
        }

        return $kommuner;
    }

    /**
     * Hent lenke til kommunens nettside
     *
     * @return String $url
     */
    public function getLink() {
        return '//'. UKM_HOSTNAME .'/'. rtrim(trim($this->getPath(),'/'),'/').'/';
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
     * @deprecated 
     * @see erAktiv()
     * @alias erAktiv
     * @return Bool $aktiv
     */
    public function erAktivt() {
        return $this->erAktiv();
    }

    /**
     * Har kommunen overtatt for andre?
     *
     * @deprecated 
     * @return Bool $har_overtatt
     */
    public function harTidligere() {
        // Deprecated - i 2022 er det ikke releavnt lenger å ha tidligere navn for kommuner
        return false;
        // return $this->tidligere !== false;
    }

    /**
     * Hvis kommunen har overtatt for andre,
     * last inn og returner disse
     * @deprecated 
     * @return Array<Kommune> $inaktive kommuner
     */ 
    public function getTidligere()
    {
        // Deprecated - i 2022 er det ikke releavnt lenger å ha tidligere navn for kommuner
        return [];

        // if( $this->tidligere === false ) {
        //     return [];
        // }

        // if( $this->tidligere === null ) {
        //     $this->_loadTidligere();
        // }
        // return $this->tidligere;
    }

    /**
     * Hent ID-listen for tidligere kommuner
     *
     * @deprecated 
     * @return String CSV ID-liste
     */
    public function getTidligereIdList() {
        // Deprecated - i 2022 er det ikke releavnt lenger å ha tidligere navn for kommuner
        return '';
        // return rtrim($this->tidligere_list,',');
    }

    /**
     * Hent en navne-liste over tidligere kommunenavn
     * 
     * @return String tom eller navneliste, prefixed med "Tidligere: "
     */
    public function getTidligereNavnListe() {
        if( !$this->harTidligere() ) {
            return '';
        }
        $string = '';
        foreach( $this->getTidligere() as $tidligere ) {
            if( sizeof( $this->getTidligere() ) == 1 && $tidligere->getNavn() != $this->getNavn() ) {
                $string .= $tidligere->getNavn() .', ';
            } else {
                $string .= $tidligere->getNavn() .', ';
            }
        }
        if( empty( $string ) ) {
            return '';
        }
        return 'Tidligere: '. rtrim( $string, ', ');
    }

    /**
     * Last inn kommuner denne kommunen har overtatt for
     *
     * @return void
     */
    private function _loadTidligere() {
        $this->tidligere = [];
        $sql = new Query(
            static::getLoadQuery(). "
            WHERE `id` IN(#liste)",
            [
                'liste' => rtrim($this->tidligere_list,',')
            ]
        );
        $res = $sql->run();

        while( $row = Query::fetch( $res ) ) {
            $tidligere = new Kommune( $row );

            $navn_for = strtolower( $tidligere->getNavn() );
            $navn_na = strtolower( $this->getNavn() );

            if( Query::numRows( $res ) > 1 ) {
                $this->tidligere[] = $tidligere;
            }
            // Hvis kommunen hadde samme navn før og nå, så er det
            // snakk om en teknisk flytting på grunn av nye fylke-
            // og kommunenumre.
            //
            // En mer brukervennlig sjekk er om dagens navn også inneholder det forrige navnet,
            // og i tilfelle anse det som samme sak.
            elseif( strpos( $navn_na, $navn_for ) === false ) {
                $this->tidligere[] = $tidligere;
            }
        }
    }

    /**
     * Er kommunen overtatt av en annen?
     *
     * @return Bool
     */
    public function erOvertatt() {
        return $this->overtatt;
    }

    /**
     * Hent kommunen som har overtatt for denne
     *
     * @return Kommune
     */
    public function getOvertattAv() {
        return $this->_findRealOvertatt( $this->overtatt_av );
    }

    /**
     * Rekursivt søk etter den kommunen som faktisk har overtatt
     * Håndterer altså kommuner som er overtatt gang etter gang etter gang 😭
     *
     * @param Int $kommune_id
     * @return Kommune
     */
    private function _findRealOvertatt( Int $kommune_id ) {
        $kommune = new Kommune( $kommune_id );
        if( $kommune->erOvertatt() ) {
            return $this->_findRealOvertatt( $kommune->getOvertattAv()->getId() );
        }
        return $kommune;
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
     * Hent kommunens område-objekt
     *
     * @return Omrade
     */
    public function getOmrade() {
        if( null == $this->omrade ) {
            $this->omrade = Omrade::getByKommune( $this->getId() );
        }
        return $this->omrade;
    }

    /**
     * Hent kommunens nettverksområde
     *
     * @return Omrade
     */
    public function getNettverkOmrade() {
        return $this->getOmrade();
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
                [
                    'UKMNorge\Geografi\Kommune',
                    'kommune'
                ]
            );
    }

    /**
     * Sikre at path er innenfor vår standard
     *
     * @ developer: hvis denne endres, må også funksjonen i UKMNorge\Wordpress\Blog endres!
     * 
     * @param String $path
     * @return String $path
     */
    public static function sanitizePath(String $path)
    {
        return preg_replace(
            "/[^a-z0-9-]/",
            '',
            str_replace(
                ['æ', 'ø', 'å', 'ü', 'é', 'è'],
                ['a', 'o', 'a', 'u', 'e', 'e'],
                mb_strtolower($path)
            )
        );
    }
}