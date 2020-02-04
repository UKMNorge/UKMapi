<?php

namespace UKMNorge\Twig;

require_once('UKM/Autoloader.php');
require_once('lib/autoload.php');

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use UKMNorge\Twig\Definitions\Filters as FilterDefinitions;

class Filters extends AbstractExtension
{
    public function getFilters()
    {
        $filters = [];
        
        $definitionClass = new FilterDefinitions();
        foreach( get_class_methods( $definitionClass ) as $function ) {
            $filters[] = new TwigFilter($function, [$definitionClass,$function]);
        }
        return $filters;
    }
}