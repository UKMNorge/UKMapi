<?php

namespace UKMNorge\Filmer\UKMTV;

use Exception;
use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Filmer\UKMTV\Tags\Tag;
use UKMNorge\Filmer\UKMTV\Tags\Tags;

class Write
{

    /**
     * Slett en film fra UKM-TV
     *
     * @param Film $film
     * @return Bool
     */
    public static function slett(Film $film)
    {
        $sql = new Update(
            'ukm_tv_files',
            [
                'tv_id' => $film->getId()
            ]
        );
        $sql->add('tv_deleted', 'true');
        return $sql->run();
    }


    public static function opprett(
        String $title,
        String $description,
        String $file,
        String $image,
        Int $season
    ) {
        throw new Exception('Implementering mangler for opprett()');
    }

    /**
     * Lagre tags for en film
     *
     * @param FilmInterface $film
     * @throws Exception
     * @return bool true
     */
    public static function saveTags( FilmInterface $film ) {
        
        static::deleteRemovedTags($film);

        foreach( $film->getTags()->getAllInkludertManyCollections() as $tag ) {
            $insert = new Insert('ukm_tv_tags');
            $insert->add('tv_id', $film->getTvId());
            $insert->add('type', $tag->getId());
            $insert->add('foreign_id', $tag->getValue());
            if($film instanceof CloudflareFilm) {
                $insert->add('is_cloudflare', 1);
            }
            $insert->run();
        }
        
        return true;
    }

    /**
     * Slett alle tags filmen ikke lengre trenger
     *
     * Itererer over alle tags i databasen, og kjører en slett-
     * spørring for hver av de som vi ikke finner igjen i gitt
     * films TagsCollection
     * 
     * @param FilmInterface $film
     * @return true
     */
    public static function deleteRemovedTags( FilmInterface $film ) {
        $db = new Query(
            "SELECT *
            FROM `ukm_tv_tags`
            WHERE `tv_id` = '#tv_id'",
            [
                'tv_id' => $film->getTvId()
            ]
        );

        $res = $db->run();

        while($row = Query::fetch($res)){
            $delete = true;
            // mange-til-mange-relasjon, sjekk også for foreign_key
            if( Tags::erMultiTag( $row['type'] ) ) {
                $alle_tags = $film->getTags()->getManyCollectionFor( $row['type'] )->getAll();
                foreach( $alle_tags as $film_tag ) {
                    if( $film_tag->getValue() == $row['foreign_id'] ) {
                        $delete = false;
                        break;
                    }
                }
            }
            // en-til-en-relasjon, og filmen har tag'en fortsatt
            else if( $film->getTags()->har( new Tag($row['type'], intval($row['foreign_id'])))) {
                $delete = false;
            }

            if( $delete ) {
                $delete = new Delete(
                    'ukm_tv_tags',
                    [
                        'tv_id' => $film->getId(),
                        'type' => $row['type'],
                        'foreign_id' => $row['foreign_id']
                    ]
                );
                $delete->run();
            }
        }
        
        return true;
    }



    /**
     * Importer eller oppdater en film til UKM-TV
     *
     * @param FilmInterface $film
     * @return void
     */
    public static function import( FilmInterface $film ) {
        return static::save($film);
    }

    /**
     * Lagre eller opprett en film i UKM-TV
     *
     * @param FilmInterface $film
     * @return bool true
     */
    public static function save( FilmInterface $film ) {
        if( empty( $film->getTvId())) {
            $try = new Query(
                "SELECT `tv_id`
                FROM `ukm_tv_files`
                WHERE `tv_file` = '#file'",
                
                [
                    'file' => $film->getFilePath()
                ]
            );
            $tv_id = $try->getField();
        } else {
            $tv_id = $film->getTvId();
        }
        
        
        if(!$tv_id) {
            $action = 'opprett';
            $query = new Insert('ukm_tv_files');
        } else {
            $action = 'oppdater';
            $query = new Update(
                'ukm_tv_files',
                [
                    'tv_id' => $tv_id
                ]
            );
        }

        $query->add('cron_id', $film->getCronId());
        $query->add('pl_id', $film->getArrangementId());
        $query->add('tv_title', $film->getTitle());
        $query->add('tv_file', $film->getFilePath());
        $query->add('tv_img', $film->getImagePath());
        $query->add('b_id', $film->getInnslagId());
        $query->add('tv_description', $film->getDescription());
        $query->add('season', $film->getSeason());
        
        $res = $query->run();
        // $res vil @ update gi mysql_affected_rows tilbake,
        // som ved dobbelt-lagring vil være 0 (fordi ingen endringer),
        // men det er jo fortsatt suksess
        if(!$res && !($action == 'oppdater' && $res === 0)) {
            throw new Exception(
                'Kunne ikke '. $action.'e filmen i UKM-TV',
                515004
            );
        }
        
        if( $action == 'opprett' ) {
            $tv_id = $res;
        }

        $film->setTvId( intval($tv_id) );

        static::saveTags( $film );

        return $film->getTvId();
    }
}
