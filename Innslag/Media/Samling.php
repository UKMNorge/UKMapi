<?php
namespace UKMNorge\Innslag\Media;

require_once('UKM/Autoloader.php');

use UKMNorge\Collection;
use Exception;

class Samling extends Collection {
	var $innslag_id = false;

    /**
     * Opprett en ny artikkel-samling
     *
     * @param Int $innslag_id
     */
	public function __construct( Int $innslag_id ) {
		$this->innslag_id = $innslag_id;
	}

    /**
     * Hent innslagID for denne samlingen
     *
     * @return Int
     */
    public function getInnslagId() {
        return $this->innslag_id;
    }
}