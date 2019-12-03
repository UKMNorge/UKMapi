<?php

namespace UKMNorge\Twig;

require_once('UKM/Autoloader.php');
require_once('lib/autoload.php');

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class Filters extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('dato', [$this, 'date']),
            new TwigFilter('filesize', [$this, 'filesize']),
            new TwigFilter('kroner', [$this, 'kroner']),
            new TwigFilter('varighet', [$this, 'tid']),
            new TwigFilter('strips', [$this, 'strips']),
            new TwigFilter('oneline', [$this, 'oneline']),
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

    /**
     * TWIG-filter: |kroner
     * Formaterer beløp i kroner-format
     * 
     * @param Int $number
     * @param Int $decimals
     * @param String $decimalPoint
     * @param String $thousandsSeparator
     * 
     * @return String $formatert_belop
     */
    public function kroner($number, Int $decimals = 0, String $decimalPoint = ',', String $thousandsSeparator = ' ')
    {
        $price = number_format($number, $decimals, $decimalPoint, $thousandsSeparator);
        $price = '' . $price;
        return $price;
    }

    /**
     * TWIG-filter: |tid
     * Sekunder som Xmin | Ysek | Xm Ys
     *
     * @param Int $seconds
     * @return String formatert tid
     */
    public function tid($seconds)
    {
        $q = floor($seconds / 60);
        $r = $seconds % 60;

        if ($q == 0)
            return $r . ' sek';

        if ($r == 0)
            return $q . ' min';

        return $q . 'm ' . $r . 's';
    }

    /**
     * TWIG-filter: |date
     * Norsk dato-representasjon av dato
     *
     * @param DateTime|String $time
     * @param String $format
     * @return String $dato
     */
    public function date($time, $format)
    {
        if (is_object($time) && get_class($time) == 'DateTime') {
            $time = $time->getTimestamp();
        } elseif (is_string($time) && !is_numeric($time)) {
            $time = strtotime($time);
        }
        $date = date($format, $time);

        return str_replace(
            array(
                'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday',
                'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun',
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December',
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ),
            array(
                'mandag', 'tirsdag', 'onsdag', 'torsdag', 'fredag', 'lørdag', 'søndag',
                'man', 'tir', 'ons', 'tor', 'fre', 'lør', 'søn',
                'januar', 'februar', 'mars', 'april', 'mai', 'juni',
                'juli', 'august', 'september', 'oktober', 'november', 'desember',
                'jan', 'feb', 'mar', 'apr', 'mai', 'jun',
                'jul', 'aug', 'sep', 'okt', 'nov', 'des'
            ),
            $date
        );
    }

    /**
     * TWIG-filter: |filesize
     * Menneskelig lesbart filstørrelse
     *
     * @param Inst $size
     * @param Int $precision
     * @return String $filesize
     */
    public function filesize($size, Int $precision = 2)
    {
        for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) { }
        return round($size, $precision) . ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
    }

    /**
     * TWIG-filter |strips
     * Stripslashes
     *
     * @param String $data
     * @return String stripslashes( $data )
     */
    public function strips($data)
    {
        if (is_string($data)) {
            return stripslashes($data);
        }
        return $data;
    }

    /**
     * TWIG-filter |oneline
     * Fjerner linjeskift
     *
     * @param String $multiline
     * @return String $singelline
     */
    public function oneline( String $multiline ) {
        return str_replace(["\r","\n"],'', $multiline);
    }
}
