<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Insert;

use Exception;

require_once('UKM/Autoloader.php');


class SvarSamtykke extends SvarUser {
    const TABLE = 'rel_samtykkeskjema_version_svar';
    
    public function __construct($data) {
        parent::__construct($data);
    }
}