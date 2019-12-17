<?php

namespace UKMNorge\Twig;

use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Loader\FilesystemLoader;
use Twig\Environment;
use Twig\Extension\DebugExtension;

require_once('UKM/Autoloader.php');
require_once('lib/autoload.php');

class Twig
{

    static $paths = [];
    static $filters = [];
    static $functions = [];
    static $data = [];
    static $environment = [];
    static $extensions = [];
    static $debug = false;
    static $didRender = false;
    static $twig = null;

    /**
     * Legg til en ny template-path
     *
     * @param String $path
     * @return void
     */
    public static function addPath(String $path)
    {
        self::$paths[] = str_replace('//', '/', $path);
        if( static::$didRender ) {
            static::$twig->getLoader()->addPath(str_replace('//', '/', $path));
        }
    }

    /**
     * Legg til et nytt filter
     *
     * @param String $filter
     * @param Callable $function
     * @return void
     */
    public static function addFilter(String $filter, $function)
    {
        self::$filters[$filter] = $function;
    }
    /**
     * Legg til ny funksjon
     *
     * @param String $name
     * @param Callable $function
     * @return void
     */
    public static function addFunction(String $name, $function)
    {
        self::$functions[$name] = $function;
    }

    /**
     * Legg til en extension
     *
     * @param Any $extension
     * @return void
     */
    public static function addExtension($extension)
    {
        static::$extensions[] = $extension;
    }

    /**
     * Sett en template-datavariabel
     *
     * @param String $key
     * @param Any $value
     * @return void
     */
    public static function setData(String $key, $value)
    {
        static::$data[$key] = $value;
    }

    /**
     * Legg til Array(key[val]) med template-data
     *
     * @param Array $key_val_array
     * @return void
     */
    public static function addData(array $key_val_array)
    {
        static::$data = array_merge(static::$data, $key_val_array);
    }

    /**
     * Sett environmentVariable
     *
     * @param String $key
     * @param Any $value
     * @return void
     */
    public function setEnvironment(String $key, $value)
    {
        static::$environment[$key] = $value;
    }

    /**
     * Aktiver debug-mode
     *
     * @return void
     */
    public static function enableDebugMode()
    {
        static::$debug = true;
    }

    /**
     * Deaktiver debug-mode
     *
     * @return void
     */
    public static function disableDebugMode()
    {
        static::$debug = false;
    }

    /**
     * Initier twig
     *
     * @return void
     */
    public static function init()
    {
    }

    /**
     * Render et template
     *
     * @param String $template
     * @param Array $data
     * @return String html
     */
    public static function render(String $template, Array $data)
    {
        static::init();
        static::addData($data);

        if (static::$debug) {
            static::setEnvironment('debug', true);
            static::addExtension(new DebugExtension());
        }

        if( !static::$didRender ) {
            static::_prepare();
        }
        static::$didRender = true;

        return static::$twig->render($template, static::getData());
    }

    /**
     * Forbered twig-variabel for render
     *
     * @return void
     */
    private function _prepare() {
        static::$twig = new Environment(
            static::getLoader(),
            static::getEnvironmentData()
        );

        foreach (static::getFilters() as $name => $function) {
            $filter = new TwigFilter($name, $function);
            static::$twig->addFilter($filter);
        }
        foreach (static::getFunctions() as $name => $function) {
            $function = new TwigFunction($name, $function);
            static::$twig->addFunction($function);
        }
        foreach (static::getExtensions() as $extension) {
            static::$twig->addExtension($extension);
        }
    }

    /**
     * Hent registrerte paths
     *
     * @return Array<String>
     */
    public static function getPaths()
    {
        return array_unique(static::$paths);
    }

    /**
     * Hent template-data
     *
     * @return Array
     */
    public static function getData()
    {
        return static::$data;
    }

    /**
     * Hent environmentData
     *
     * @return void
     */
    public static function getEnvironmentData()
    {
        return static::$environment;
    }

    /**
     * Opprett Twig Filesystem-loader
     *
     * @return void
     */
    public static function getLoader()
    {
        return new FilesystemLoader(self::getPaths());
    }

    /**
     * Hent alle filtre
     *
     * Array[filter_navn] = filter
     * 
     * @return Array
     */
    public static function getFilters()
    {
        return static::$filters;
    }
    /**
     * Hent alle funksjoner
     * 
     * Array[funksjonsnavn] = funksjon
     *
     * @return void
     */
    public static function getFunctions()
    {
        return static::$functions;
    }

    /**
     * Hent alle extensions
     *
     * @return Array
     */
    public static function getExtensions()
    {
        return static::$extensions;
    }

    /**
     * Sett opp Twig i standard-konfigurasjon
     *
     * @return void
     */
    public static function standardInit()
    {
        static::addPath(dirname(__DIR__) . '/Twig/templates/');
        static::setData('UKM_HOSTNAME', UKM_HOSTNAME);

        if (defined('TWIG_CACHE_PATH')) {
            static::setEnvironment('cache', TWIG_CACHE_PATH);
            static::setEnvironment('auto_reload', true);
        }

        static::addExtension( new \UKMNorge\Twig\Filters() );
        static::addExtension( new \UKMNorge\Twig\Functions() );

        putenv('LC_ALL=nb_NO');
        setlocale(LC_ALL, 'nb_NO');
    }   
}