<?php

namespace UKMNorge\Innslag\Advarsler;

use UKMNorge\Collection;

require_once('UKM/Autoloader.php');

class Advarsler extends Collection
{
    public function har($kategori)
    {
        foreach ($this as $advarsel) {
            if ($advarsel->getKategori() == $kategori) {
                return $advarsel;
            }
        }
    }
}
