<?php

namespace UKMNorge;

class Tid
{
    var $sekunder = null;

    public function __construct($sekunder = false)
    {
        if (is_numeric($sekunder)) {
            $this->setSekunder($sekunder);
        }
    }

    public function setSekunder($sekunder)
    {
        $this->sekunder = $sekunder;
    }

    public function getSekunder()
    {
        return $this->sekunder;
    }

    public function getHumanShort()
    {
        return $this->_getHuman('s', 'm');
    }

    public function getHuman()
    {
        return $this->_getHuman('sek', 'min');
    }

    public function getHumanLong()
    {
        return $this->_getHuman('sekunder', 'minutter');
    }

    public function getCompact()
    {
        list($min, $sek) = $this->_getData();

        return str_pad((string) $min, 2, '0', STR_PAD_LEFT) .
            ':' .
            str_pad((string) $sek, 2, '0', STR_PAD_LEFT);
    }


    private function _getData()
    {
        $m = floor($this->sekunder / 60);
        $s = $this->sekunder % 60;

        return [$m, $s];
    }

    private function _getHuman($sek, $min)
    {
        list($m, $s) = $this->_getData();

        if ($m == 0)
            return $s . ' ' . $sek;

        if ($s == 0)
            return $q . ' ' . $min;

        return $m . $min . ' ' . $s . $sek;
    }

    public function __toString()
    {
        return $this->getHuman();
    }
}
