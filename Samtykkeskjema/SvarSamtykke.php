<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Arrangement\Skjema\DeltaRespondent;

use Exception;

require_once('UKM/Autoloader.php');


class SvarSamtykke extends SvarUser {
    const TABLE = 'rel_samtykkeskjema_version_svar';
    
    public function __construct($data) {
        parent::__construct($data);
    }

    public function erSamtykkeGitt(): bool {
        return $this->getSvar() == 'ja';
    }

    public function erSamtykkeGittMedForesattSjekk() {
        $deltaUser = DeltaRespondent::loadById($this->getUser());
        if( $deltaUser == null ) {
            return false;
        }
        if($deltaUser->is18YearNow()) {
            return $this->erSamtykkeGitt();
        }
        // User under 18 years old, check if foresatt has given consent
        else {
            return $this->erSamtykkeGitt() && $this->getForesattIdGodkjent() != null;
        }
        
        return false;

    }
}