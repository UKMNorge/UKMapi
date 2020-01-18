<?php

namespace UKMNorge\Filmer\Upload;

class Publish {

    public static function innslag() {

        // INSERT INTO UKM-TV



        $tags = static::getMinimumTags($arrangement);
        $tags->opprett('innslag', $innslag->getId());
        foreach( $innslag->getPersoner()->getAll() as $person ) {
            $tags->opprett('person', $person->getId());
        }
        $tags->opprett('kommune', $innslag->getKommune()->getId());
        $tags->opprett('fylke', $innslag->getFylke()->getId());
        static::saveTags($tags);
    }

    public static function reportasje() {

        // INSERT INTO UKM-TV
        

        // TODO: når arrangementet har flere kommuner, burde dette kanskje komme med her?
        // eller bryr vi oss om kommuner og fylker kun for innslagsfilmer, sånn egentlig?
        $tags = static::getMinimumTags($arrangement);
        if( $arrangement->getEierType() == 'kommune') {
            $tags->opprett('kommune', $arrangement->getKommune()->getId());
        } elseif($arrangement->getEierType() == 'fylke') {
            $tags->opprett('fylke', $arrangement->getFylke()->getId());
        }
        static::addTags($tags);
    }


        /**
     * Opprett en Tags-collection med minimumstags lagt til (arrangement)
     *
     * @param Arrangement $arrangement
     * @return Tags
     */
    public static function getMinimumTags(Arrangement $arrangement)
    {
        $tags = new Tags();
        $tags->opprett('arrangement', $arrangement->getId());
        $tags->opprett('arrangement_type', Tags::getArrangementTypeId( $arrangement->getEierType() ));
        $tags->opprett('sesong', $arrangement->getSesong());

        return $tags;
    }

    public static function saveTags( Int $tv_id, Tags $tags ) {

        foreach( $tags->getAll() as $tag ) {
            $insert = new Insert(
                'ukm_tv_tags'
            );
            $insert->add('tv_id')
        }

        foreach( $tags->getPersoner()->getAll() as $tag ) {

        }
    }
}
