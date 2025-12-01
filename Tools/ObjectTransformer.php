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
            'paameldingsfrist_1' => $arrangement->getFrist1()->getTimestamp(),
            'paameldingsfrist_2' => $arrangement->getFrist2()->getTimestamp(),
            'type' => $arrangement->getType(),
            'path' => $arrangement->getPath(),
            'kommuner' => $arrangement->getType() == 'kommune' ? $kommunerArr : [],
            'fylke' => $arrangement->getFylke(),
            'paamelding_lenker' => $arrangement->getPaameldingsLenker(),
            'utvidet_gui' => $arrangement->getGuiType() == 1 ?? false,
            'beskrivelse' => $arrangement->getBeskrivelse(),
        ];
    }

    // $erUKMKontakt brukes for å tilpasse data for UKM Norge nasjonalt nivå sine kontaktpersoner
    public static function kontaktperson(OmradeKontaktperson $kontaktperson, bool $erUKMKontakt=false) : array {
        return [
            'id' => self::generateKontaktpersonID($kontaktperson),
            'navn' => $kontaktperson->getFornavn() . ' ' . $kontaktperson->getEtternavn(),
            'beskrivelse' => $erUKMKontakt ? ($kontaktperson->getBeskrivelse() ?? '') : '',
            'epost' => $kontaktperson->getEpost() ?? '',
            'tel' => $kontaktperson->getTelefon(),
            'bilde' => $kontaktperson->getBilde() ?? '',
        ];
    }

    public static function adminKontaktperson($adminKontaktperson, $bilde) : array {
        return [
            'id' => self::generateKontaktpersonID($adminKontaktperson),
            'navn' => $adminKontaktperson['display_name'],
            'epost' => $adminKontaktperson['user_email'] ?? '',
            'tel' => $adminKontaktperson['user_phone'] ?? 'Ukjent telefon',
            'bilde' => $bilde ?? '',
        ];
    }

    private static function generateKontaktpersonID($kontaktperson) : string {
        $navn = '';
        $telefon = '';

        if($kontaktperson instanceof OmradeKontaktperson) {
            // Hvis det er en OmradeKontaktperson, bruk id direkte
            $navn = $kontaktperson->getFornavn() . $kontaktperson->getEtternavn();
            $telefon = $kontaktperson->getTelefon();
        } else {
            $navn = $kontaktperson['display_name'] ?? '';
            $telefon = $kontaktperson['user_phone'] ?? '';
        }

        if(empty($navn) && empty($telefon)) {
            // Random text generation
            $navn = 'ukjent_' . bin2hex(random_bytes(8));
            $telefon = '0';
        }

        // make lowercase and remove spaces
        $navn = strtolower(str_replace(' ', '', $navn));
        $telefon = str_replace(' ', '', $telefon);

        return md5($navn . '_' . $telefon);
    }

    public static function kommune(Kommune $kommune) : array {
        $fylke = $kommune->getFylke();

        return [
            'id' => $kommune->getId(),
            'navn' => $kommune->getNavn(),
            'fylke_id' => $fylke ? $fylke->getId() : -1,
            'fylke_navn' => $fylke ? $fylke->getNavn() : 'Ukjent fylke',
            'path' => $kommune->getPath(),
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
            'sjanger' => $innslag->getSjanger(),
            'beskrivelse' => $innslag->getBeskrivelse(),
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
