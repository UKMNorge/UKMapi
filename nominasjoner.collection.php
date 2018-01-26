<?php
	
/* UTGÅR 
require_once('UKM/nominasjon.class.php');

class nominert_til extends _nominasjoner {
	public function __construct( $monstring_id, $monstring_type, $monstring_geografi, $monstring_sesong ) {
		parent::__construct( $monstring_id, $monstring_type, $monstring_geografi, 'til');
	}	
}

class nominert_fra extends _nominasjoner {
	public function __construct( $monstring_id, $monstring_type, $monstring_geografi, $monstring_sesong ) {
		parent::__construct( $monstring_id, $monstring_type, $monstring_geografi, $monstring_sesong, 'fra');
	}
}

class _nominasjoner {
	
	private $niva;
	private $monstring_id;
	private $monstring_type;
	private $monstring_kommune;
	private $monstring_fylke;
	private $monstring_sesong;
	private $type;
	
	private $nominasjoner_media = null;
	
	public function __construct( $monstring_id, $monstring_type, $monstring_geografi, $monstring_sesong, $type ) {
		$this->setType( $type );
		$this->setMonstringId( $monstring_id );
		$this->setMonstringType( $monstring_type );
		$this->setMonstringSesong( $monstring_sesong );
		switch( $monstring_type ) {
			case 'fylke':
				$this->setMonstringFylke( $monstring_geografi );
				break;
			case 'kommune':
				$this->setMonstringKommune( $monstring_kommune );
				break;
			case 'land':
				break;
			default:
				throw new Exception('NOMINASJONER: Ikke gitt riktig type mønstring!', 1);
		}
	}
	
	public function getMedia() {
		if( null == $this->nominasjoner_media ) {
			$this->_loadNominasjoner('media');
		}
		return $this->nominasjoner_media;
	}
	
	public function getArrangorer() {
		if( null == $this->nominasjoner_arrangor ) {
			$this->_loadNominasjoner('arrangor');
		}
		return $this->nominasjoner_arrangor;
	}
	
	public function getKonferansierer() {
		if( null == $this->nominasjoner_konferansier ) {
			$this->_loadNominasjoner('konferansier');
		}
		return $this->nominasjoner_konferansier;
	}
	
	private function _loadNominasjoner( $type ) {
		$container = 'nominasjoner_'. $type;
		$classname = 'nominasjon_'.$type;
		
		$this->$container = [];
		
		$res = $this->_getLoadSQL( $type )->run();
		
		if( !$res ) {
			throw new Exception('NOMINASJONER: Kunne ikke laste inn nominasjoner av typen '. $type, 5);
		}
		
		while( $row = mysql_fetch_assoc( $res ) ) {
			array_push( $this->$container, new $classname( $row ));
		}
	}
	
	private function _getLoadSQL( $type ) {
		if( $this->getMonstringType() == 'kommune') {
			throw new Exception('NOMINASJONER: Støtter ikke å hente ut nominasjoner fra kommuner enda', 2);
		}
		
		$geografi = '';
		$extra = '';
		// FRA MIN MØNSTRING
		if( str_replace('nominert_', '', get_class( $this ) ) == 'fra' ) {
			if( $this->getMonstringType() == 'fylke' ) {
				$niva = 'land';
				$geografi = $this->getMonstringFylke()->getId();
				$extra = "AND `ukm_nominasjon`.`fylke_id` = '#geografi'";
			} elseif( $this->getMonstringType() == 'kommune' ) {
				$niva = 'fylke';
				$geografi = $this->getMonstringKommune()->getId();
				$extra = "AND `ukm_nominasjon`.`kommune_id` = '#geografi'";
			} else {
				throw new Exception('NOMINASJONER: UKM-festivalen kan ikke videresende / nominere', 3);
			}
		}
		// TIL MIN MØNSTRING
		else {
			if( $this->getMonstringType() == 'fylke' ) {
				$niva = 'fylke';
				$geografi = $this->getMonstringFylke()->getId();
				$extra = "AND `ukm_nominasjon`.`fylke_id` = '#geografi'";
			} elseif( $this->getMonstringType() == 'land' ) {
				$niva = 'land';
			} else {
				throw new Exception('NOMINASJONER: Kommuner kan ikke ta i mot nominerte', 4);
			}
		}
		
		$niva = $this->getMonstringType() == 'fylke' ? 'land' : 'fylke';
		 
		
		return new SQL(
			nominasjon::getLoadQuery() . "
			WHERE `ukm_nominasjon`.`type` = '#type'
			AND `niva` = '#niva'
			AND `season` = '#season'
			" . $extra,
			[
				'table' => nominasjon::getDetailTable( $type ),
				'type' => $type,
				'niva' => $niva,
				'season' => $this->getMonstringSesong(),
				'geografi' => $geografi
			]
		);
	}
	
	public function getMonstringId(){
		return $this->monstring_id;
	}

	public function setMonstringId($monstring_id){
		$this->monstring_id = $monstring_id;
		return $this;
	}

	public function getMonstringType(){
		return $this->monstring_type;
	}

	public function setMonstringType($monstring_type){
		$this->monstring_type = $monstring_type;
		return $this;
	}

	public function getMonstringKommune(){
		return $this->monstring_kommune;
	}

	public function setMonstringKommune($monstring_kommune){
		$this->monstring_kommune = $monstring_kommune;
		return $this;
	}

	public function getMonstringFylke(){
		return $this->monstring_fylke;
	}

	public function setMonstringFylke($monstring_fylke){
		$this->monstring_fylke = $monstring_fylke;
		return $this;
	}

	public function getType(){
		return $this->type;
	}

	public function setType($type){
		$this->type = $type;
		return $this;
	}
	
	public function getMonstringSesong() {
		return $this->monstring_sesong;
	}
	public function setMonstringSesong( $sesong ) {
		$this->monstring_sesong = $sesong;
		return $this;
	}
}
*/