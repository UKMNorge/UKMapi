<?php

namespace UKMNorge\Samtykke\Meldinger;

require_once('UKM/Autoloader.php');

/**
 * MELDING TIL FORESATTE
 */

// FORESATTE (deltakeren har sagt "ja, dette er ok", men trenger go fra foresatt)
class ForesattDeltakerHarGodkjent extends Foresatt {
	public static function getMelding() {
		return 'Hei! Om det ikke er ønskelig at vi tar bilder og/eller film av %navn på UKM, '.
			' må vi ha beskjed. %fornavn har selv sagt at det er greit. '.
			'Les mer og svar på lenken nedenfor. '.
			SMS::LINK_FORESATT;
	}
}