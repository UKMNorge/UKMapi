<?php

namespace UKMNorge\Arrangement;

class Filter {
    var $filters = [];

    public function harPamelding() {
        $this->filters['pamelding'] = true;
    }

    public function byEier( Eier $eier ) {
        $this->filters['eier'] = $eier;
    }

    public function doFilterAll( Array $arrangementer ) {
        $filtered = [];

        foreach( $arrangementer as $arrangement ) {
            if( $this->passesFilter( $arrangement ) ) {
                $filtered[] = $arrangement;
            }
        }
        return $filtered;
    }

    public function passesFilter( Arrangement $arrangement ) {
        foreach( $this->filters as $filter_key => $filter_values ) {
            switch( $filter_key ) {
                case 'pamelding':
                    if( !$this->_filterPamelding( $arrangement ) ) {
                        return false;
                    }
                break;
                case 'eier':
                    if( !$this->_filterEier( $arrangement ) ) {
                        return false;
                    }
                break;
            }
        }
        return true;
    }
    

    private function _filterPamelding( Arrangement $arrangement ) {
        return $arrangement->harPamelding();
    }

    private function _filterEier( Arrangement $arrangement ) {
        return $arrangement->getEier()->getId() == $this->filters['eier']->getId();
    }
}