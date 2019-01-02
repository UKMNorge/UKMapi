<?php

namespace UKMNorge\Samtykke\Meldinger;

/**
 * MELDING TIL FORESATTE
 */

// FORESATTE (deltakeren har ikke sagt ja allerede)
class Foresatt {
	public static function getTemplateDefinition() {
		return [
			'link_id'	=> 'Lenke-ID for voksnes samtykkeskjema',
			'navn'		=> 'Deltakerens navn',
			'alder'		=> 'Deltakerens alder',
			'kategori'	=> 'Navn på kategorien personen inngår i (under 15 osv)',
			'fornavn'	=> 'Deltakerens fornavn',
		];
	}
	
	public static function getMelding() {
		return 'Hei! %fornavn ønsker ikke å bli avbildet eller filmet på UKM. Vi kan ikke garantere dette. '.
			'Les mer og gi din respons på lenken nedenfor. '.
			SMS::LINK_FORESATT;
	}
}

// FORESATTE (deltakeren har sagt "ja, dette er ok", men trenger go fra foresatt)
class ForesattDeltakerHarGodkjent extends Foresatt {
	public static function getMelding() {
		return 'Hei! Om det ikke er ønskelig at vi tar bilder og/eller film av %navn på UKM, '.
			' må vi ha beskjed. %fornavn har selv sagt at det er greit. '.
			'Les mer og svar på lenken nedenfor. '.
			SMS::LINK_FORESATT;
	}
}