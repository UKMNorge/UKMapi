<?php

namespace UKMNorge\Twig;

require_once('UKM/Autoloader.php');
require_once('lib/autoload.php');

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use UKMNorge\Twig\Definitions\Functions as FunctionDefinitions;

class Functions extends AbstractExtension
{
    public function getFunctions()
    {
        $functions = [];
        $definitionClass = new FunctionDefinitions();
        foreach( get_class_methods( $definitionClass ) as $function ) {
            $functions[] = new TwigFunction($function, [$definitionClass,$function]);
        }
        return $functions;
    }
}