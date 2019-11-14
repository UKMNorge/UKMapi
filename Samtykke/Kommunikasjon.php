<?php
    
namespace UKMNorge\Samtykke;
use Exception;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Samtykke\Meldinger\Melding;
use UKMNorge\Samtykke\Meldinger\SMS;

class Kommunikasjon {
	var $id = null;
	var $meldinger = null;
	
	public function __construct( $samtykke_id ) {
		$this->id = $samtykke_id;
	}
	
	public function getAll() {
		if( null == $this->meldinger ) {
			$this->_load();
		}
		return $this->meldinger;
	}
	
	public function har( $melding_type ) {
		try {
			$melding = $this->get( $melding_type );
			if( is_object( $melding ) ) {
				return true;
			}
		} catch( Exception $e ) { }

		return false;
	}
	
	public function get( $melding_type ) {
		foreach( $this->getAll() as $melding ) {
			if( $melding->getType() == $melding_type ) {
				return $melding;
			}
		}
		
		throw new Exception('Beklager, det finnes ingen slike meldinger for denne personen');
	}
	
	public function _load() {
		$sql = new Query("
			SELECT * 
			FROM `samtykke_deltaker_kommunikasjon`
			WHERE `samtykke_id` = '#id'
			ORDER BY `id` DESC",
			[
				'id' => $this->getId(),
			]
		);
		$res = $sql->run();
		$this->meldinger = [];
		while( $row = Query::fetch( $res ) ) {
			$this->meldinger[] = new Melding( $row );
		}
	}
    
    /**
     * Hent Samtykke\Person-ID
     * 
     * @return int Samtykke\Person::id
     */
	public function getId() {
		return $this->id;
    }
    
    /**
     * Send gitt $melding_type til deltakeren / foresatte
     * 
     * Sjekker om meldingen er sendt tidligere (purring kan sendes flere ganger),
     * sender meldingen og logger dette.
     * 
     * @param string $melding_type
     * @return string Meldingen som ble sendt, eller årsak til at den ikke ble sendt.
     */
    public function sendMelding( $melding_type ) {
        switch( $melding_type ) {
            case 'samtykke':
            case 'samtykke_foresatt':
                if( $this->har( $melding_type ) ) {
                    $melding = $this->get( $melding_type);
                    return 'Kunne ikke sende melding da den allerede er sendt tidligere ('. $melding->getTimestamp() .')';
                } else {
                    return $this->_send( $melding_type );
                }
            break;
            case 'purring_deltaker':
            case 'purring_foresatt':
                // TODO: BURDE SJEKKE TID SIDEN SIST
                return $this->_send( $melding_type );

            default:
                throw new Exception('Beklager, støtter ikke sending av meldinger av typen `'. $melding_type .'`');
        }
    }


    /**
     * Faktisk send melding. Trigges av sendMelding() som sjekker
     * om meldingen er sendt tidligere eller, ikke.
     * 
     * Henter automatisk samtykke-objekt og oppdaterer dette
     * Lagrer også kommunikasjon i databasen for historikkens skyld
     * 
     * @param string $melding_type
     * @return string Meldingen som ble sendt
     */
    private function _send( $melding_type ) {
        $samtykke_object = Person::getById( $this->getId() );
        return SMS::send( $melding_type, $samtykke_object );
    }
}