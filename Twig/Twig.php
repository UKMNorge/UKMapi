<?php

namespace UKMNorge\Twig;

use Twig_Autoloader, Twig_Environment, Twig_Loader_Filesystem, Twig_Extension_Debug, Twig_SimpleFilter, Twig_SimpleFunction;

class Twig
{

    static $paths = [];
    static $filters = [];
    static $functions = [];
    static $data = [];
    static $environment = [];
    static $extensions = [];
    static $debug = false;

    public static function addPath(String $path)
    {
        self::$paths[] = str_replace('//','/',$path);
    }

    public static function addFilter(String $filter, $function)
    {
        self::$filters[$filter] = $function;
    }
    public static function addFunction(String $name, $function)
    {
        self::$functions[$name] = $function;
    }
    public static function addExtension($extension)
    {
        static::$extensions[] = $extension;
    }

    public static function setData(String $key, $value)
    {
        static::$data[$key] = $value;
    }

    public static function addData( Array $key_val_array ) {
        static::$data = array_merge( static::$data, $key_val_array );
    }

    public function setEnvironment(String $key, $value)
    {
        static::$environment[$key] = $value;
    }

    public static function enableDebugMode()
    {
        static::$debug = true;
    }

    public static function disableDebugMode() {
        static::$debug = false;
    }

    public static function init()
    {
        require_once('Twig/Autoloader.php');
        Twig_Autoloader::register();
    }

    public static function render(String $template, array $data)
    {
        static::init();
        static::addData( $data );

        if( static::$debug ) {
            static::setEnvironment('debug', true);
            static::addExtension(new Twig_Extension_Debug());
        }

        $twig = new Twig_Environment(
            static::getLoader(),
            static::getEnvironmentData()
        );

        foreach (static::getFilters() as $name => $function) {
            $filter = new Twig_SimpleFilter($name, $function);
            $twig->addFilter($filter);
        }
        foreach (static::getFunctions() as $name => $function) {
            $function = new Twig_SimpleFunction($name, $function);
            $twig->addFunction($function);
        }
        foreach (static::getExtensions() as $extension) {
            $twig->addExtension($extension);
        }

        return $twig->render($template, static::getData());
    }

    public static function getPaths()
    {
        return array_unique(static::$paths);
    }

    public static function getData()
    {
        return static::$data;
    }

    public static function getEnvironmentData()
    {
        return static::$environment;
    }

    public static function getLoader()
    {
        return new Twig_Loader_Filesystem(self::getPaths());
    }

    public static function getFilters()
    {
        return static::$filters;
    }
    public static function getFunctions()
    {
        return static::$functions;
    }
    public static function getExtensions()
    {
        return static::$extensions;
    }
}
