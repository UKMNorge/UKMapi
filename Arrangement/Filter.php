<?php

namespace UKMNorge\Arrangement;

class Filter
{
    var $filters = [];

    public function sesong(Int $sesong)
    {
        $this->filters['sesong'] = $sesong;
    }

    public function harPamelding()
    {
        $this->filters['pamelding'] = true;
    }

    public function erKommende()
    {
        $this->filters['kommende'] = true;
    }

    public function erTidligere()
    {
        $this->filters['tidligere'] = true;
    }

    public function byEier(Eier $eier)
    {
        $this->filters['eier'] = $eier;
    }

    /**
     * Sørg for at gitte arrangement består alle filter-krav
     *
     * @param Array<Arrangement>
     * @return Array<Arrangement>
     */
    public function doFilterAll(array $arrangementer)
    {
        $filtered = [];

        foreach ($arrangementer as $arrangement) {
            if ($this->passesFilter($arrangement)) {
                $filtered[] = $arrangement;
            }
        }
        return $filtered;
    }

    /**
     * Sørg for at gitt arrangement består alle filter-krav
     *
     * @param Arrangement $arrangement
     * @return Bool
     */
    public function passesFilter(Arrangement $arrangement)
    {
        foreach ($this->filters as $filter_key => $filter_values) {
            switch ($filter_key) {
                case 'pamelding':
                    if (!$this->_filterPamelding($arrangement)) {
                        return false;
                    }
                    break;
                case 'eier':
                    if (!$this->_filterEier($arrangement)) {
                        return false;
                    }
                    break;
                case 'kommende':
                    if ($this->_filterErGjennomfort($arrangement)) {
                        return false;
                    }
                    break;
                case 'tidligere':
                    if (!$this->_filterErGjennomfort($arrangement)) {
                        return false;
                    }
                case 'sesong':
                    if (!$this->_filterSesong($arrangement, $filter_values)) {
                        return false;
                    }
                    break;
            }
        }
        return true;
    }

    /**
     * Hent ut alle aktive filtre
     *
     * @return Array
     */
    public function getFilters() {
        return $this->filters;
    }


    /**
     * Hent verdien for sesong-filteret hvis dette er satt
     *
     * @return Int|Bool
     */
    public function getSesong() {
        if( isset($this->filters['sesong']) ) {
            return $this->filters['sesong'];
        }
        return false;
    }


    /**
     * Finn arrangement som tar i mot påmelding fra deltakere
     * 
     * OBS: brukes som oftest i sammenheng med erKommende()
     *
     * @param Arrangement $arrangement
     * @return Bool
     */
    private function _filterPamelding(Arrangement $arrangement)
    {
        return $arrangement->harPamelding();
    }

    /**
     * Finn arrangement med gitt eier
     *
     * @param Arrangement $arrangement
     * @return Bool
     */
    private function _filterEier(Arrangement $arrangement)
    {
        return $arrangement->getEier()->getId() == $this->filters['eier']->getId();
    }

    /**
     * Finn arrangement som er gjennomført
     *
     * @param Arrangement $arrangement
     * @return Bool
     */
    private function _filterErGjennomfort(Arrangement $arrangement)
    {
        return $arrangement->erFerdig();
    }

    /**
     * Finn arrangement for gitt sesong
     *
     * @param Arrangement $arrangement
     * @param Int $sesong
     * @return Bool
     */
    private function _filterSesong(Arrangement $arrangement, Int $sesong)
    {
        return $arrangement->getSesong() == $sesong;
    }
}
