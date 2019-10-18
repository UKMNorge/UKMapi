<?php

use UKMNorge\Twig\Twig as TwigAdmin;

require_once('UKM/Twig/Twig.php');
require_once('UKMconfig.inc.php');

function TWIG($template, $dataarray, $templatefolder, $debug = false)
{
    TwigAdmin::standardInit();
    // Add template and default paths
    TwigAdmin::addPath($templatefolder . '/twig/');
    if (class_exists('UKMwp_innhold')) {
        TwigAdmin::addPath(UKMwp_innhold::getPath() . 'twig/');
    }
    TwigAdmin::addData($dataarray);

    TwigAdmin::enableDebugMode($debug);

    $template = $template . (strpos($template, '.html.twig') === false ? '.twig.html' : '');
    $template = str_replace('.twig.html.twig.html', '.twig.html', str_replace(':', DIRECTORY_SEPARATOR, $template));

    return TwigAdmin::render($template, $dataarray);
}

function TWIGrender($template, $dataarray, $debug = false)
{
    return TWIG($template, $dataarray, str_replace('/twig/', '', TWIG_PATH), $debug);
}
