<?php

namespace UKMNorge\Innslag\Nominasjon;

use Exception;
use UKMNorge\Collection;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Innslag\Typer\Type;

class Nominasjoner extends Collection {
    private $innslag_id;
    private $innslag_type;

    public function __construct( Int $innslag_id, Type $innslag_type )
    {
        $this->innslag_id = $innslag_id;
        $this->innslag_type = $innslag_type;
    }

    /**
     * Last inn alle nominasjoner for innslaget
     *
     * @return void
     */
    public function _load() {
        $query = new Query(
            "SELECT `id` 
            FROM `ukm_nominasjon`
            WHERE `b_id` = '#innslag'",
            [
                'innslag' => $this->innslag_id
            ]
        );
        $res = $query->getResults();
        while( $row = Query::fetch( $res ) ) {
            switch ($this->innslag_type->getKey()) {
                case 'nettredaksjon':
                case 'media':
                    $this->add( Media::getById( intval($row['id']), $this->innslag_type ));
                    break;
                case 'konferansier':
                    $this->add( Konferansier::getById( intval($row['id']), $this->innslag_type ));
                    break;
                case 'arrangor':
                    $this->add( Arrangor::getById( intval($row['id']), $this->innslag_type ));
                    break;
                default:
                    $this->add( new Placeholder() );
                    break;
            }
        }
    }

    /**
     * Sett hvilket arrangement vi jobber fra for øyeblikket
     *
     * @param Int $arrangement_id
     * @return self
     */
    public function setFra( Int $arrangement_id ) {
        $this->fra = $arrangement_id;
        return $this;
    }

    /**
     * Hent hvilket arrangement vi jobber fra for øyeblikket
     *
     * @return Int 
     */
    public function getFraId() {
        return $this->fra;
    }

    /**
     * Hent nominasjon for innslaget til gitt arrangement
     *
     * @param Int $arrangement_id
     * @return Nominasjon
     * @throws Exception
     */
    public function getTil( Int $arrangement_id ) {
        foreach( $this->getAll() as $nominasjon ) {
            if( $nominasjon->getTilArrangementId() == $arrangement_id ) {
                return $nominasjon;
            }
        }

        if( is_null( $this->getFraId() ) || empty( $this->getFraId() )) {
            throw new Exception(
                'Kan ikke opprette ny nominasjon uten at fra-arrangement er satt',
                122003
            );
        }
        return $this->createNominasjon( $this->getFraId(), $arrangement_id );
    }

    /**
     * Opprett nominasjonsobjekt for innslaget (lagrer ikke!)
     *
     * @param Int $fra_arrangement_id
     * @param Int $til_arrangement_id
     * @return Nominasjon
     */
    public function createNominasjon( Int $fra_arrangement_id, Int $til_arrangement_id ) {
        switch ($this->innslag_type->getKey()) {
            case 'nettredaksjon':
            case 'media':
                $nominasjon = Media::getByInnslagData( $this->innslag_id, $this->innslag_type, $fra_arrangement_id, $til_arrangement_id);
                break;
            case 'konferansier':
                $nominasjon = Konferansier::getByInnslagData( $this->innslag_id, $this->innslag_type, $fra_arrangement_id, $til_arrangement_id);
                break;
            case 'arrangor':
                $nominasjon = Arrangor::getByInnslagData( $this->innslag_id, $this->innslag_type, $fra_arrangement_id, $til_arrangement_id);
                break;
            default:
                $nominasjon = new Placeholder();
                break;
        }
        $this->add($nominasjon);
        return $nominasjon;
    }

    /**
     * Har innslaget en nominasjon til gitt arrangement?
     *
     * @param Int $arrangement_id
     * @return Bool
     */
    public function harTil( Int $arrangement_id ) {
        try {
            $this->getTil($arrangement_id);
            return true;
        } catch( Exception $e ) {
        }
        return false;
    }
}