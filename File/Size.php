<?php

namespace UKMNorge\File;

class Size
{
    /**
     * This function transforms the php.ini notation for numbers (like '2M') to an integer (2*1024*1024 in this case)
     * 
     * Thanks to: https://stackoverflow.com/a/22500394
     * 
     * @param String $sSize
     * @return Int The value in bytes
     */
    public static function convertPHPSizeToBytes(String $sSize)
    {
        //
        $sSuffix = strtoupper(substr($sSize, -1));
        if (!in_array($sSuffix, array('P', 'T', 'G', 'M', 'K'))) {
            return (int) $sSize;
        }
        $iValue = substr($sSize, 0, -1);
        switch ($sSuffix) {
            case 'P':
                $iValue *= 1024;
                // Fallthrough intended
            case 'T':
                $iValue *= 1024;
                // Fallthrough intended
            case 'G':
                $iValue *= 1024;
                // Fallthrough intended
            case 'M':
                $iValue *= 1024;
                // Fallthrough intended
            case 'K':
                $iValue *= 1024;
                break;
        }
        return intval($iValue);
    }

    /**
     * Hent menneskelig lesbar størrelse
     *
     * @param Int|Float $size
     * @param Int $precision
     * @return String
     */
    public static function getHuman($size, Int $precision = 2) {
        for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024) { }
        return round($size, $precision) . ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
    }
}
