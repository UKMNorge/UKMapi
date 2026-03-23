<?php

namespace UKMNorge\Samtykkeskjema;

use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Arrangement\Skjema\Skjema;

use Exception;

require_once('UKM/Autoloader.php');


class SvarSporreskjema extends SvarUser {
    const TABLE = 'rel_sporeskjema_svar';

    protected ?Skjema $sporreskjema = null;

    public function __construct($data) {
        parent::__construct($data);
    }

    public function getSporreskjema(): ?Skjema {
        return $this->sporreskjema;
    }

    public function setSporreskjema(Skjema $skjema): void {
        $this->sporreskjema = $skjema;
    }
}