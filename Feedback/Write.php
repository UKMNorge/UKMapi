<?php
    
namespace UKMNorge\Feedback;
use Exception;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;

require_once('UKM/Autoloader.php');

class Write {
		
	
	public static function saveFeedback(Feedback $feedback ) {		
		$sql = new Insert('feedback');
		
        $sql->add('user_id', $feedback->getUserId() );
		$sql->add('fornavn', $fornavn);
		$sql->add('etternavn', $etternavn);
		$sql->add('mobil', $mobil);
		$sql->add('melding', $melding_fixed);
		$sql->add('lenker', json_encode( $lenker ));
		$sql->add('hash', $hash );
		$sql->add('hash-excerpt', $hashexcerpt );
		$insert_id = $sql->run();
		
		return new Request( $insert_id );
	}
	
	public static function godta( $request, $alder ) {
		$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		$hash = sha1( $request->getProsjektId() .'-'. $request->getId() .'-'. $alder .'-'. $ip );
		$hashexcerpt = substr( $hash, 6, 10 );
		
		$sql = new Insert('samtykke_approval');
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
		
		return new Approval( $request->getId() );
	}
	
		
	public static function godtaForesatt( $request ) {
		$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		$hash = sha1( $request->getId() .'-'. $request->getProsjektId() .'-'. $ip );
		$hashexcerpt = substr( $hash, 6, 10 );
		
		$sql = new Insert('samtykke_approval_foresatt');
		$sql->add('approval', $request->getApproval()->getId() );
		$sql->add('ip', $ip );
		$sql->add('hash', $hash );
		$sql->add('hash-excerpt', $hashexcerpt );
		$res = $sql->run();
		
		return new Approval( $request->getId() );
	}
	
	
	
	public static function lagreForesatt( $request, $navn, $mobil ) {
		$sql = new Update(
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