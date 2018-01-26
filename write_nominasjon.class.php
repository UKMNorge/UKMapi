<?php
require_once('UKM/nominasjon.class.php');
require_once('UKM/nominasjon_voksen.class.php');

class write_nominasjon extends nominasjon {
	public static function create( $innslag_id, $sesong, $niva, $kommune, $innslag_type ) {
		if( !UKMlogger::ready() ) {
			throw new Exception('Logger is missing or incorrect set up.');
		}
		
		if( $innslag_type == 'nettredaksjon' ) {
			$innslag_type = 'media';
		}
		
		if( !is_numeric( $innslag_id ) ) {
			throw new Exception('WRITE_NOMINASJON: Krever numerisk innslags-id');
		}
		if( !is_numeric( $sesong ) || strlen( $sesong ) != 4 ) {
			throw new Exception('WRITE_NOMINASJON: Krever numerisk sesong, 4 sifre');
		}
		if( !in_array( $niva, ['fylke','land'] ) ) {
			throw new Exception('WRITE_NOMINASJON: Støtter kun fylke- og land-nivå');
		}
		if( !in_array( $innslag_type, ['arrangor','konferansier','media'] ) ) {
			throw new Exception('WRITE_NOMINASJON: Støtter kun fylke- og land-nivå');
		}
		if( 'kommune' != get_class($kommune) ) {
			throw new Exception("WRITE_NOMINASJON: Krever kommune-objekt, ikke ".get_class($kommune)."." );
		}

		$classname = 'nominasjon_'. $innslag_type;
		
		$obj = new $classname( $innslag_id, $innslag_type, $niva );
		if( !$obj->harNominasjon() ) {
			$sql = new SQLins('ukm_nominasjon');
			$sql->add('b_id', $innslag_id );
			$sql->add('season', $sesong);
			$sql->add('niva', $niva);
			$sql->add('kommune_id', $kommune->getId());
			$sql->add('fylke_id', $kommune->getFylke()->getId());
			$sql->add('type', $innslag_type );
			$res = $sql->run();
			
			if( !$res ) {
				throw new Exception('WRITE_NOMINASJON: Kunne ikke opprette nominasjon!');
			}
			
			$sql2 = new SQLins('ukm_nominasjon_'. $innslag_type);
			$sql2->add('nominasjon', $sql->insId());
			$res2 = $sql2->run();
			
			if( !$res2 ) {
				throw new Exception('WRITE_NOMINASJON: Kunne ikke opprette nominasjon (opprettelse detaljrad feilet)');
			}
	
			$obj = new $classname( $innslag_id, $innslag_type, $niva );
	
			if( !$obj ) {
				throw new Exception('WRITE_NOMINASJON: Noe feilet ved opprettelsen av nominasjonen');
			}
		}
		return $obj;
	}
	
	public static function saveNominertState( $nominasjon, $state ) {
		if( !UKMlogger::ready() ) {
			throw new Exception('Logger is missing or incorrect set up.');
		}
		
		if( !is_object( $nominasjon ) ) {
			throw new Exception('WRITE_NOMINASJON: Lagring av tilstand krever et nominasjons-objekt som parameter 1');
		}
		
		if( !is_numeric( $nominasjon->getId() ) ) {
			throw new Exception('WRITE_NOMINASJON: Lagring av nominasjons-tilstand krever objekt med numerisk id');
		}

		$sql = new SQLins(
			'ukm_nominasjon',
			[
				'id' => $nominasjon->getId(),
			]
		);
		$sql->add('nominert', $state ? 'true':'false');
		$sql->run();
		
		return true;
	}
	
	public static function createVoksen( $nominasjon_id ) {
		if( !UKMlogger::ready() ) {
			throw new Exception('Logger is missing or incorrect set up.');
		}
		
		if( !is_numeric( $nominasjon_id ) ) {
			throw new Exception('WRITE_NOMINASJON_VOKSEN: Krever numerisk nominasjons-id');
		}

		try {
			$obj = new nominasjon_voksen( $nominasjon_id );
		} catch( Exception $e ) {
			$sql = new SQLins('ukm_nominasjon_voksen');
			$sql->add('nominasjon', $nominasjon_id );
			$res = $sql->run();
			
			if( !$res ) {
				throw new Exception('WRITE_NOMINASJON_VOKSEN: Kunne ikke opprette voksen!');
			}

			$obj = new nominasjon_voksen( $nominasjon_id );
		}
		return $obj;
	}

	public static function saveVoksen( $voksen ) {
		if( !UKMlogger::ready() ) {
			throw new Exception('Logger is missing or incorrect set up.');
		}
		
		if( get_class( $voksen ) != 'nominasjon_voksen' ) {
			throw new Exception('WRITE_NOMINASJON_VOKSEN: Lagring av voksen krever voksen-objekt som parameter');
		}
		
		if( !is_numeric( $voksen->getId() ) ) {
			throw new Exception('WRITE_NOMINASJON_VOKSEN: Lagring av voksen krever numerisk id');
		}
		
		$sql = new SQLins(
			'ukm_nominasjon_voksen',
			[
				'id' => $voksen->getId(),
				'nominasjon' => $voksen->getNominasjon()
			]
		);
		$sql->add('navn', $voksen->getNavn());
		$sql->add('mobil', $voksen->getMobil());
		$sql->add('rolle', $voksen->getRolle());
		$res = $sql->run();

		return true;
	}
	
	public static function saveMedia( $nominasjon ) {
		if( !UKMlogger::ready() ) {
			throw new Exception('Logger is missing or incorrect set up.');
		}
		
		if( get_class( $nominasjon ) != 'nominasjon_media' ) {
			throw new Exception('WRITE_NOMINASJON_MEDIA: Lagring av media-detaljer krever objekt som parameter');
		}
		
		if( !is_numeric( $nominasjon->getId() ) ) {
			throw new Exception('WRITE_NOMINASJON_MEDIA: Lagring av media-detaljer krever numerisk id');
		}

		$sql = new SQLins('ukm_nominasjon_media', ['nominasjon' => $nominasjon->getId() ] );
		$sql->add('pri_1', $nominasjon->getPri1() );
		$sql->add('pri_2', $nominasjon->getPri2() );
		$sql->add('pri_3', $nominasjon->getPri3() );
		$sql->add('annet', $nominasjon->getAnnet() );
		$sql->add('beskrivelse', $nominasjon->getBeskrivelse() );
		$sql->add('samarbeid', $nominasjon->getSamarbeid() );
		$sql->add('erfaring', $nominasjon->getErfaring() );
		$res = $sql->run();
	}
}