<?php

namespace UKMNorge\Twig\Definitions;

class Functions {
    /**
     * TWIG-funksjon: GET()
     * Hent $_GET-variabel
     *
     * @param String $GET_key
     * @return void
     */
    public function GET($GET_key)
    {
        if (isset($_GET[$GET_key])) {
            return $_GET[$GET_key];
        }
        return false;
    }

    public function UKMasset($path)
	{
		if ( defined('UKM_HOSTNAME') && UKM_HOSTNAME == 'ukm.dev' ) {
			return '//ukm.dev/wp-content/themes/UKMDesign/_GRAFIKK_UKM_NO/'. $path;
		}
		return '//grafikk.ukm.no/UKMresponsive/'. $path;
	}
}