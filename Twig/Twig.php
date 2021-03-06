<?php

namespace UKMNorge\Twig;

use \LogicException;
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
    static $filter_options;

    /**
     * Legg til en ny template-path
     *
     * @param String $path
     * @return void
     */
    public static function addPath(String $path)
    {
        self::$paths[] = str_replace('//', '/', $path);
        if (static::$didRender) {
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
    public static function addFilter(String $filter, $function, $options=null)
    {
        self::$filters[$filter] = $function;
        self::$filter_options[$filter] = $options;
    }

    /**
     * Legg til flere filter fra én klasse
     *
     * @param $class
     * @return void
     */
    public static function addFiltersFromClass($class)
    {
        foreach (get_class_methods($class) as $function) {
            static::addFilter($function, [$class, $function]);
        }
    }

    /**
     * Legg til flere funksjoner fra én klasse
     *
     * @param $class
     * @return void
     */
    public static function addFunctionsFromClass($class)
    {
        foreach (get_class_methods($class) as $function) {
            static::addFunction($function, [$class, $function]);
        }
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
    public static function setEnvironment(String $key, $value)
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
    { }

    /**
     * Render et template
     *
     * @param String $template
     * @param Array $data
     * @return String html
     */
    public static function render(String $template, array $data)
    {
        static::init();
        static::addData($data);

        if (static::$debug) {
            static::setEnvironment('debug', true);
            static::addExtension(new DebugExtension());
        }

        if (!static::$didRender) {
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
    private static function _prepare()
    {
        static::$twig = new Environment(
            static::getLoader(),
            static::getEnvironmentData()
        );

        foreach (static::getFilters() as $name => $function) {
            if( isset( static::$filter_options[$name] ) && !is_null(static::$filter_options[$name]) ) {
                $filter = new TwigFilter($name, $function, static::$filter_options[$name]);
            } else {
                $filter = new TwigFilter($name, $function);

            }
                
            try {
                static::$twig->addFilter($filter);
            } catch( LogicException $e ) {
                // ignorer LogicException (som betyr at den er lagt til fra før)
            }
        }
        foreach (static::getFunctions() as $name => $function) {
            $function = new TwigFunction($name, $function);
            try {
                static::$twig->addFunction($function);
            } catch( LogicException $e ) {
                // ignorer LogicException (som betyr at den er lagt til fra før)
            }
        }
        foreach (static::getExtensions() as $extension) {
            try {
                static::$twig->addExtension($extension);
            } catch( LogicException $e ) {
                // ignorer LogicException (som betyr at den er lagt til fra før)
            }
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
     * @return FilesystemLoader
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
     * @return Array<callable>
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

        static::addExtension(new \UKMNorge\Twig\Filters());
        static::addExtension(new \UKMNorge\Twig\Functions());

        putenv('LC_ALL=nb_NO');
        setlocale(LC_ALL, 'nb_NO');
    }
}
