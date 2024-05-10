<?php

namespace UKMNorge\SearchArrangorsystemet;
use UKMNorge\SearchArrangorsystemet\Keyword;
use UKMNorge\Database\SQL\Insert;


use UKMNorge\Database\SQL\Query;

class Write{
    public static function createKeyword(int $contentIndexId, int $weight, Keyword $keyword) : Keyword {
        // Check if keyword exists
        $sqlCheck = new Query(
            "SELECT keyword_id FROM ukm_search_as_keyword WHERE keyword_name = '#name'",
            [
                'name' => $keyword->getName()
            ]
        );
        
        $res = $sqlCheck->run('array');
        // If keyword exists, get id
        if($res) {
            $keywordId = $res['keyword_id'];
        // If keyword does not exist, create it
        } else {
            $sql = new Query(
                "INSERT INTO ukm_search_as_keyword (keyword_name) VALUES ('#name');",
                [
                    'name' => $keyword->getName()
                ]
            );
            $res = $sql->run();

            // Assigning id to keyword
            $resId = $sqlCheck->run('array');
            $keywordId = $resId['keyword_id'];
        }

        // Insert connection
        $sqlConnection = new Query(
            "INSERT IGNORE INTO ukm_search_as_content_keyword (index_id, keyword_id, weight) VALUES ('#indexId', '#keywordId', '#weight')",
            [
                'indexId' => $contentIndexId,
                'keywordId' => $keywordId,
                'weight' => $weight
            ]
        );
            
        $sqlConnection->run();

        $kw = new Keyword($keywordId, $keyword->getName());
        $kw->setWeight($weight);

        return $kw;
    }

    public static function deleteKeyword(string $contentIndexId, string $keywordId) {
        $sql = new Query(
            "DELETE FROM ukm_search_as_content_keyword WHERE index_id = '#indexId' AND keyword_id = '#keywordId'",
            [
                'indexId' => $contentIndexId,
                'keywordId' => $keywordId
            ]
        );

        $sql->run();

        return true;
    }

    public static function createContentIndex(string $title, string $siteUrl, string $description, int $contextId) {
        $sql = new Insert("ukm_search_as_content_index");
        $sql->add('title', $title);
        $sql->add('site_url', $siteUrl);
        $sql->add('description', $description);
        $sql->add('context_id', $contextId);

		$insert_id = $sql->run(); 

        return new ContentIndex($insert_id, $siteUrl, $title, $description, null, $contextId);
    }
}