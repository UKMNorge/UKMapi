<?php

namespace UKMNorge\Innslag\Mangler\Mangel;

use UKMNorge\Innslag\Innslag;
use UKMNorge\Innslag\Mangler\Mangel;
use UKMNorge\Innslag\Typer\Type as InnslagType;
use UKMNorge\Innslag\Titler\Tittel as InnslagTittel;
use UKMNorge\Innslag\Mangler\Mangler;

class Tittel
{
    public static function evaluer(InnslagType $type, InnslagTittel $tittel)
    {
        switch ($type->getKey()) {
            case 'dans':
                return static::evaluerDans($tittel);
            case 'litteratur':
                return static::evaluerLitteratur($tittel);
            case 'teater':
                return static::evaluerTeater($tittel);
            case 'musikk':
                return static::evaluerMusikk($tittel);
            case 'annet':
            case 'scene':
                return static::evaluerScene($tittel);
            case 'film':
            case 'video':
                return static::evaluerFilm($tittel);
            case 'kunst':
            case 'utstilling':
                return static::evaluerUtstilling($tittel);
        }

        return true;
    }

    /**
     * Evaluer en matkultur-tittel
     *
     * @param InnslagTittel $tittel
     * @return void
     */
    public static function evaluerMatkultur(InnslagTittel $tittel)
    {
        $mangler = [];
        $mangler[] = static::_evaluerTittel($tittel, 'Tittel uten navn', 'Tittelen har ikke fått et navn');
        return Mangler::manglerOrTrue( $mangler );
    }

    /**
     * Evaluer et kunstverk
     *
     * @param InnslagTittel $tittel
     * @return Array<Mangel>
     */
    public static function evaluerUtstilling(InnslagTittel $tittel)
    {
        $mangler = [];
        $mangler[] = static::_evaluerTittel($tittel, 'Kunstverk uten navn', 'Kunstverket har ikke fått et navn');

        if (empty($tittel->getType())) {
            $mangler[] = new Mangel(
                'tittel.type',
                'Kunstverk uten type',
                'Det mangler hva kunstverket er (type)',
                'tittel',
                $tittel->getId()
            );
        }
        return Mangler::manglerOrTrue( $mangler );
    }
    /**
     * Evaluer en film-tittel
     *
     * @param InnslagTittel $tittel
     * @return void
     */
    public static function evaluerFilm(InnslagTittel $tittel)
    {
        $mangler = [];
        $mangler[] = static::_evaluerTittel($tittel, 'Film uten navn', 'Filmen har ikke fått et navn');
        $mangler[] = static::_evaluerTid($tittel, 'Film uten varighet', 'Det er ikke oppgitt hvor lenge filmen varer');
        return Mangler::manglerOrTrue( $mangler );
    }

    /**
     * Evaluer en musikk-tittel
     *
     * @param InnslagTittel $tittel
     * @return Array<Mangel>
     */
    public static function evaluerMusikk(InnslagTittel $tittel)
    {
        $mangler = [];
        $mangler[] = static::_evaluerTittel($tittel, 'Låt uten navn', 'Låten har ikke fått et navn');
        $mangler[] = static::_evaluerTid($tittel, 'Låt uten varighet', 'Det er ikke oppgitt hvor lenge låten varer');
        
        if (!$tittel->erSelvlaget() && empty($tittel->getTekstAv()) && !$tittel->erInstrumental()) {
            $mangler[] = new Mangel(
                'tittel.tekstav',
                'Låt uten tekstforfatter',
                'Det er ikke oppgitt hvem som har laget teksten til låten',
                'tittel',
                $tittel->getId()
            );
        }

        if (!$tittel->erSelvlaget() && empty($tittel->getMelodiAv())) {
            $mangler[] = new Mangel(
                'tittel.melodiav',
                'Låt uten komponist',
                'Det er ikke oppgitt hvem som har laget melodien til låten',
                'tittel',
                $tittel->getId()
            );
        }

        return Mangler::manglerOrTrue( $mangler );
    }
    /**
     * Evaluer en generisk scene-tittel
     *
     * @param InnslagTittel $tittel
     * @return Array<Mangel>
     */
    public static function evaluerScene(InnslagTittel $tittel)
    {
        $mangler = [];
        $mangler[] = static::_evaluerTittel($tittel, 'Tittel uten navn', 'Tittelen har ikke fått et navn');
        $mangler[] = static::_evaluerTid($tittel, 'Tittel uten varighet', 'Det er ikke oppgitt hvor lenge tittelen varer');
        
        return Mangler::manglerOrTrue( $mangler );
    }

    /**
     * Evaluer en teater-tittel
     *
     * @param InnslagTittel $tittel
     * @return Array<Mangel>
     */
    public static function evaluerTeater(InnslagTittel $tittel)
    {
        $mangler = [];
        $mangler[] = static::_evaluerTittel($tittel, 'Sketsj/stykke uten tittel', 'Sketsjen/stykket har ikke tittel');
        $mangler[] = static::_evaluerTid($tittel, 'Sketsj/stykke uten varighet', 'Det er ikke oppgitt hvor lenge sketsjen/stykket varer');

        if (!$tittel->erSelvlaget() && empty($tittel->getTekstAv())) {
            $mangler[] = new Mangel(
                'tittel.manus',
                'Sketsj/stykke uten manus',
                'Det er ikke oppgitt hvem som har laget manus til sketsjen/stykket',
                'tittel',
                $tittel->getId()
            );
        }
        if( sizeof($mangler)==0) {
            return true;
        }
        return Mangler::manglerOrTrue( $mangler );
    }

    /**
     * Evaluer en litteratur-tittel
     *
     * @param InnslagTittel $tittel
     * @return Array<Mangel>
     */
    public static function evaluerLitteratur(InnslagTittel $tittel)
    {
        $mangler = [];
        $mangler[] = static::_evaluerTittel($tittel, 'Tekst/verk uten tittel', 'Teksten/verket har ikke tittel');
        if ($tittel->skalLesesOpp()) {
            $mangler[] = static::_evaluerTid($tittel, 'Tekst/verk uten varighet', 'Det er ikke oppgitt hvor lenge teksten/verket varer');
        }
        if( sizeof($mangler)==0) {
            return true;
        }
        return Mangler::manglerOrTrue( $mangler );
    }

    /**
     * Evaluer en dans
     *
     * @param InnslagTittel $tittel
     * @return Array<Mangel>
     */
    public static function evaluerDans(InnslagTittel $tittel)
    {
        $mangler = [];

        $mangler[] = static::_evaluerTittel($tittel, 'Dans uten tittel', 'Dansen har ikke tittel');
        $mangler[] = static::_evaluerTid($tittel, 'Dans uten varighet', 'Det er ikke oppgitt hvor lenge dansen varer');

        if (!$tittel->erSelvlaget() && empty($tittel->getKoreografiAv())) {
            $mangler[] = new Mangel(
                'tittel.koreografi',
                'Dans uten koreografi',
                'Det er ikke oppgitt hvem som har laget koreografien til dansen',
                'tittel',
                $tittel->getId()
            );
        }
        if( sizeof($mangler)==0) {
            return true;
        }
        return Mangler::manglerOrTrue( $mangler );
    }

    /**
     * Sjekk tittelens tittel (navn, altså)
     *
     * @param InnslagTittel $tittel
     * @param String $navn
     * @param String $forklaring
     * @return Mangel|Bool true
     */
    private static function _evaluerTittel(InnslagTittel $tittel, String $navn, String $forklaring)
    {
        if (empty($tittel->getTittel())) {
            return new Mangel(
                'tittel.tittel',
                $navn,
                $forklaring,
                'tittel',
                $tittel->getId()
            );
        }
        return true;
    }

    /**
     * Evaluer tittelens varighet
     *
     * @param InnslagTittel $tittel
     * @param String $navn
     * @param String $forklaring
     * @return Mangel|Bool true
     */
    private static function _evaluerTid(InnslagTittel $tittel, String $navn, String $forklaring)
    {
        if ($tittel->getSekunder() == 0) {
            return new Mangel(
                'tittel.varighet',
                $navn,
                $forklaring,
                'tittel',
                $tittel->getId()
            );
        }
        return true;
    }    
}
