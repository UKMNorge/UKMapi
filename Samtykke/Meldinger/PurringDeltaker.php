<?php

namespace UKMNorge\Samtykke\Meldinger;

/**
 * PURRING
 */

// TIL DELTAKER
class PurringDeltaker {
	public static function getTemplateDefinition() {
		return [
			'link_id'	=> 'Lenke-ID for samtykkeskjema',
			'navn'		=> 'Deltakerens navn',
			'fornavn'	=> 'Deltakerens fornavn',
		];
	}
	
	public static function getMelding() {
		return 'Hei %fornavn! Vi trenger et svar fra deg om bilder og film på UKM.'. "\r\n ".
			'Gi oss beskjed på lenken nedenfor. '.
			SMS::LINK;
	}
}