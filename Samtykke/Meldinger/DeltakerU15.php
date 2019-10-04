<?php

namespace UKMNorge\Samtykke\Meldinger;

require_once('UKM/Autoloader.php');

// DELTAKERE UNDER 15
class DeltakerU15 extends Deltaker {
	public static function getMelding() {
        return 'Hei %fornavn! Som du sikkert vet, har du blitt påmeldt UKM. '.
            'Vi trenger mobilnummeret til en av dine foreldre/foresatte. '.
            'Gi oss dette og si fra hvis du ikke vil at UKM skal ta bilder av deg her: ' .
            SMS::LINK;
	}
}