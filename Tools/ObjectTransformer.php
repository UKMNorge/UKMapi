<?php

namespace UKMNorge\Tools;

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Program\Hendelse;
use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Media\Bilder\Bilde;
use UKMNorge\Filmer\UKMTV\FilmInterface;

use Exception;
use UKMNorge\Filmer\UKMTV\Film;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Nettverk\OmradeKontaktperson;

class ObjectTransformer {

    public static function arrangement(Arrangement $arrangement) : array{
        $kommunerArr = [];
        foreach($arrangement->getKommuner() as $kommune) {
            $kommunerArr[] = self::kommune($kommune);
        }

        return [
            'id' => $arrangement->getId(),
            'navn' => $arrangement->getNavn(),
            'url' => $arrangement->getLink(),
            'sted' => $arrangement->getSted(),
            'start' => $arrangement->getStart()->getTimestamp(),
            'stop' => $arrangement->getStop()->getTimestamp(),
            'path' => $arrangement->getPath(),
            'kommuner' => $kommunerArr,
            'fylke' => $arrangement->getFylke(),
        ];
    }

    public static function kontaktperson(OmradeKontaktperson $kontaktperson) : array {
        return [
            'id' => $kontaktperson->getId(),
            'fornavn' => $kontaktperson->getFornavn(),
            'etternavn' => $kontaktperson->getEtternavn(),
            'epost' => $kontaktperson->getEpost(),
            'telefon' => $kontaktperson->getTelefon(),
            'tittel' => $kontaktperson->getTittel(),
        ];
    }

    public static function kommune(Kommune $kommune) : array {
        $fylke = $kommune->getFylke();

        return [
            'id' => $kommune->getId(),
            'navn' => $kommune->getNavn(),
            'fylke_id' => $fylke ? $fylke->getId() : -1,
            'fylke_navn' => $fylke ? $fylke->getNavn() : 'Ukjent fylke',
        ];
    }

    public static function hendelse(Hendelse $hendelse) : array {
        $innslagArr = [];
        foreach($hendelse->getInnslag()->getAll() as $innslag) {
            $innslagArr[] = self::innslag($innslag);
        }
        
        return [
            'id' => $hendelse->getId(),
            'navn' => $hendelse->getNavn(),
            'start' => $hendelse->getStart()->getTimestamp(),
            'synlig_i_rammeprogram' => $hendelse->erSynligRammeprogram(),
            'synlig_detaljprogram' => $hendelse->erSynligDetaljprogram(),
            'sted' => $hendelse->getSted(),
            'innslag' => $innslagArr,
        ];
    }

    public static function innslag(Innslag $innslag) : array {
        $obj = [
            'id' => $innslag->getId(),
            'navn' => $innslag->getNavn(),
            'type' => $innslag->getType() ? $innslag->getType()->getNavn() : 'Ukjent type',
        ];

        // Legg til personer
        $obj['personer'] = [];
        foreach($innslag->getPersoner()->getAll() as $person) {
            $obj['personer'][] = [
                'id' => $person->getId(),
                'navn' => $person->getNavn(),
                'fornavn' => $person->getFornavn(),
                'etternavn' => $person->getEtternavn(),
            ];
        }
        return $obj;
    }

    public static function bilde(Bilde $bilde) : array {
        return [
            'id' => $bilde->getId(),
            'album_id' => $bilde->getAlbumId(),
            'sizes' => $bilde->sizes,
        ];
    }

    public static function film(FilmInterface $film) : array {
        return [
            'id' => $film->getId(),
            'title' => $film->getTitle(),
            'description' => $film->getDescription(),
            'thumbnail_url' => $film->getImagePath(),
            'embed_url' => $film->getEmbedUrl(),
        ];
    }
}
