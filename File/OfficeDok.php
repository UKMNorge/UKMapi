<?php

namespace UKMNorge\File;

use Exception;

abstract class OfficeDok {
    static $path;
    static $url;
    var $orientation = 'portrait';
    var $name;

    public function __construct( String $filnavn )
    {
        if( !is_dir( static::$path ) ) {
            throw new Exception(
                'Kan ikke opprette filen, da systemet mangler mappen det skal lagres i. '.
                'Kontakt <a href="mailto:support@ukm.no?subject=UKMAPI%2FFile%2FExcel-eller-Word feil satt opp">support@ukm.no</a>',
                401002
            );
        }
        $this->name = $filnavn;
    }

    /**
     * Sett dokumentets retning
     * landskap eller portrett (default)
     * 
     * @param String $orientation
     * @return  self
     */ 
    public function setRetning($retning)
    {
        if( !in_array($retning, ['portrett','landskap'] ) ) {
            throw new Exception(
                'Excel-dokumenter støtter kun portrett eller landskap',
                401001
            );
        }
        $this->orientation = $retning == 'portrett' ? 'portrait' : 'landscape';

        return $this;
    }

        /**
     * Hent mappen det skal skrives til
     *
     * @return void
     */
    public function getPath() {
        return rtrim( static::$path, '/') .'/'. date('Y') .'/';
    }
    /**
     * Hent URL-base
     *
     * @return void
     */
    public function getUrl() {
        return rtrim( static::$url, '/') .'/'. date('Y') .'/';
    }

    /**
     * Angi hvor på serveren filene skal lagres
     *
     * @param String $path
     * @return void
     */
    public static function setPath( String $path ) {
        static::$path = $path;
    }

    /**
     * Angi URL for nedlasting av filer
     *
     * @param String $url
     * @return void
     */
    public static function setUrl(String $url ) {
        static::$url = $url;
    }
}