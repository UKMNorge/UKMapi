<?php

namespace UKMNorge\Innslag\Advarsler;

require_once('UKM/Autoloader.php');
require_once('UKM/_collection.class.php');

class Advarsler extends \Collection
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
