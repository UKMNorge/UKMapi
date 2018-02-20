<?php
	
class write_samtykke {
	
	public static function createProsjekt( $tittel ) {
		require_once('UKM/samtykke/prosjekt.class.php');
		$sql = new SQLins('samtykke_prosjekt');
		$sql->add('tittel', $tittel);
		$res = $sql->run();

		$id = $sql->insId();
			
		if( $res == -1 ) {
			$error = $sql->error();
			if( strpos($error, 'Duplicate') == 0 ) {
				throw new Exception('Et annet prosjekt med samme tittel finnes fra før');
			}
			throw new Exception('Kunne ikke opprette samtykke-prosjekt, ukjent feil');
		}
		if( is_numeric( $id ) ) {
			return new samtykke_prosjekt( $id );
		}
		
		throw new Exception('Kunne ikke opprette samtykke-prosjekt');
	}
	
	public static function saveProsjekt( $prosjekt ) {
		require_once('UKM/samtykke/prosjekt.class.php');
		$prosjekt_db = new samtykke_prosjekt( $prosjekt->getId() );
		
		$sql = new SQLins(
			'samtykke_prosjekt',
			[
				'id' => $prosjekt->getId()
			]
		);
		// VERDIER SOM KAN OPPDATERES
		$properties = [
			'Tittel'		=> ['tittel'],
			'Setning'		=> ['setning'],
			'Varighet'		=> ['varighet'],
			'Beskrivelse'	=> ['beskrivelse'],
			'Hash'			=> ['hash'],
		];
		
		// LOOP ALLE VERDIER, OG EVT LEGG TIL I SQL
		foreach( $properties as $functionName => $db ) {
			$function = 'get'.$functionName;
			
			if( $prosjekt_db->$function() != $prosjekt->$function() ) {
				$sql->add( $db[0], $prosjekt->$function() );
			}
		}
		$sql->add('hash-excerpt', substr( $prosjekt->getHash(), 6, 10 ) );
		
		if( !$sql->hasChanges() ) {
			return true;
		}
		
		$res = $sql->run();
		if( $res ) {
			return true;
		}
		
		throw new Exception('Kunne ikke lagre samtykke-prosjekt');
	}
	
	public static function lockProsjekt( $prosjekt ) {
		
		if( $prosjekt->isLocked() ) {
			return true;
		}
		
		$sql = new SQLins(
			'samtykke_prosjekt',
			[
				'id' => $prosjekt->getId()
			]
		);
		$sql->add('locked', 'true');
		$res = $sql->run();
		if( $res ) {
			return true;
		}
		throw new Exception('Kunne ikke låse prosjektet!');
	}
	
	
	public static function createRequest( $prosjekt, $melding, $lenker, $fornavn, $etternavn, $mobil ) {
		require_once('UKM/samtykke/request.class.php');

		$hash = sha1( $fornavn .'-'. $etternavn .'-'. $mobil .'-'. var_export($lenker,true) );
		$hashexcerpt = substr( $hash, 6, 10 );
		$melding_fixed = samtykke_request::createMelding( $prosjekt, $melding, $lenker, $fornavn, $mobil, $hashexcerpt );
		
		
		$sql = new SQLins('samtykke_request');
		$sql->add('prosjekt', $prosjekt->getId() );
		$sql->add('fornavn', $fornavn);
		$sql->add('etternavn', $etternavn);
		$sql->add('mobil', $mobil);
		$sql->add('melding', $melding_fixed);
		$sql->add('lenker', json_encode( $lenker ));
		$sql->add('hash', $hash );
		$sql->add('hash-excerpt', $hashexcerpt );
		$res = $sql->run();
		
		return new samtykke_request( $sql->insId() );
	}
	
	public static function godta( $request, $alder ) {
		require_once('UKM/samtykke/prosjekt.class.php');
		require_once('UKM/samtykke/request.class.php');
		require_once('UKM/samtykke/approval.class.php');
		
		$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		$hash = sha1( $request->getProsjektId() .'-'. $request->getId() .'-'. $alder .'-'. $ip );
		$hashexcerpt = substr( $hash, 6, 10 );
		
		$sql = new SQLins('samtykke_approval');
		$sql->add('prosjekt', $request->getProsjektId() );
		$sql->add('request', $request->getId() );
		$sql->add('prosjekt-request', $request->getProsjektId().'-'.$request->getId() );
		$sql->add('alder', $alder);
		if( $alder == 'over20' or (int) $alder >= 15 ) {
			$sql->add('trenger_foresatt', 'false');
		}
		$sql->add('ip', $ip );
		$sql->add('hash', $hash );
		$sql->add('hash-excerpt', $hashexcerpt );
		$res = $sql->run();
		
		return new samtykke_approval( $request->getId() );
	}
	
		
	public static function godtaForesatt( $request ) {
		require_once('UKM/samtykke/prosjekt.class.php');
		require_once('UKM/samtykke/request.class.php');
		require_once('UKM/samtykke/approval.class.php');
		
		$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		$hash = sha1( $request->getId() .'-'. $request->getProsjektId() .'-'. $ip );
		$hashexcerpt = substr( $hash, 6, 10 );
		
		$sql = new SQLins('samtykke_approval_foresatt');
		$sql->add('approval', $request->getApproval()->getId() );
		$sql->add('ip', $ip );
		$sql->add('hash', $hash );
		$sql->add('hash-excerpt', $hashexcerpt );
		$res = $sql->run();
		
		return new samtykke_approval( $request->getId() );
	}
	
	
	
	public static function lagreForesatt( $request, $navn, $mobil ) {
		require_once('UKM/samtykke/prosjekt.class.php');
		require_once('UKM/samtykke/request.class.php');
		require_once('UKM/samtykke/approval.class.php');

		$sql = new SQLins(
			'samtykke_approval', 
			[
				'request' => $request->getId(),
				'prosjekt' => $request->getProsjektId()
			]
		);
		$sql->add('foresatt_navn', $navn );
		$sql->add('foresatt_mobil', $mobil );
		
		$res = $sql->run();
	}
}