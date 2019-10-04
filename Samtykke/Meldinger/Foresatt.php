<?php

namespace UKMNorge\Samtykke\Meldinger;

require_once('UKM/Autoloader.php');

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