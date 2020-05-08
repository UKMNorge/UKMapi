<?php

namespace UKMNorge\Filmer\Upload;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Filmer\UKMTV\Tags\Tags as UKMTVTags;
use UKMNorge\Innslag\Innslag;

class Tags {
    /**
     * Opprett en UKMTV Tag-collection for en innslag-film
     *
     * @param Arrangement $arrangement
     * @param Innslag $innslag
     * @return UKMTVTags
     */
    public static function getForInnslag( Arrangement $arrangement, Innslag $innslag ) {
        $tags = static::_getForArrangement($arrangement);

        $tags->opprett('innslag', $innslag->getId());
        foreach( $innslag->getPersoner()->getAll() as $person ) {
            $tags->opprett('person', $person->getId());
        }

        $tags->opprett('kommune', $innslag->getKommune()->getId());
        $tags->opprett('fylke', $innslag->getFylke()->getId());
        
        return $tags;
    }

    /**
     * Opprett en UKMTV Tag-collection for en reportasje
     *
     * @param Arrangement $arrangement
     * @return UKMTVTags
     */
    public static function getForReportasje( Arrangement $arrangement ) {
        // TODO: når arrangementet har flere kommuner, burde dette kanskje komme med her?
        // eller bryr vi oss om kommuner og fylker kun for innslagsfilmer, sånn egentlig?
        $tags = static::_getForArrangement($arrangement);
        
        if( $arrangement->getEierType() == 'kommune' && $arrangement->erSingelmonstring() ) {
            $tags->opprett('kommune', $arrangement->getKommune()->getId());
        } elseif($arrangement->getEierType() == 'fylke') {
            $tags->opprett('fylke', $arrangement->getFylke()->getId());
        }
        
        return $tags;
    }


    /**
     * Opprett en Tags-collection med minimumstags lagt til (arrangement)
     *
     * @param Arrangement $arrangement
     * @return UKMTVTags
     */
    private static function _getForArrangement(Arrangement $arrangement)
    {
        $tags = new UKMTVTags();
        $tags->opprett('arrangement', $arrangement->getId());
        $tags->opprett('arrangement_type', UKMTVTags::getArrangementTypeId( $arrangement->getEierType() ));
        $tags->opprett('sesong', $arrangement->getSesong());

        return $tags;
    }

    
}
