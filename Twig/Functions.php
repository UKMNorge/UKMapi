<?php

namespace UKMNorge\Twig;

require_once('UKM/Autoloader.php');
require_once('lib/autoload.php');

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;


class Functions extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('GET', [$this, 'GET']),
        ];
    }

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
}