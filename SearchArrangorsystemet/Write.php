<?php

namespace UKMNorge\SearchArrangorsystemet;
use UKMNorge\SearchArrangorsystemet\Keyword;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Update;
use UKMNorge\Database\SQL\Delete;



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
            $sql = new Insert('ukm_search_as_keyword');
            $sql->add('keyword_name', $keyword->getName());
            $res = $sql->run();

            // Assigning id to keyword
            $resId = $sqlCheck->run('array');
            $keywordId = $resId['keyword_id'];
        }

        // Insert connection
        $sqlConnection = new Insert('ukm_search_as_content_keyword');
        $sqlConnection->add('index_id', $contentIndexId);
        $sqlConnection->add('keyword_id', $keywordId);
        $sqlConnection->add('weight', $weight/100);
        $sqlConnection->add('timestamp', date('Y-m-d H:i:s', time()));


        // $sqlConnection = new Query(
        //     "INSERT IGNORE INTO ukm_search_as_content_keyword (index_id, keyword_id, weight, timestamp) VALUES ('#indexId', '#keywordId', '#weight' , FROM_UNIXTIME('#timestamp'))",
        //     [
        //         'indexId' => $contentIndexId,
        //         'keywordId' => $keywordId,
        //         'weight' => ($weight/100),
        //         'timestamp' => time()
        //     ]
        // );

        $sqlConnection->run();

        $kw = new Keyword($keywordId, $keyword->getName(), $weight);
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

    // Content Index

    public static function createContentIndex(string $title, string $siteUrl, string $description, int $contextId) {
        $sql = new Insert("ukm_search_as_content_index");
        $sql->add('title', $title);
        $sql->add('site_url', $siteUrl);
        $sql->add('description', $description);
        $sql->add('context_id', $contextId);

		$insert_id = $sql->run(); 

        return new ContentIndex($insert_id, $siteUrl, $title, $description, null, $contextId);
    }

    public static function updateContentIndex(ContentIndex $contentIndex) {
        $query = new Update(
            'ukm_search_as_content_index',
            [
                'index_id' => $contentIndex->getId()
            ]  
        );
        $query->add('title', $contentIndex->getTitle());
        $query->add('site_url', $contentIndex->getSiteUrl());
        $query->add('description', $contentIndex->getDescription());
        $query->add('context_id', $contentIndex->getContextId());

        $res = $query->run();

        return $res;
    }

    public static function deleteContentIndex(ContentIndex $contentIndex) {
        // Delete all keyword connections
        $sqlConnections = new Delete(
            'ukm_search_as_content_keyword',
            [
                'index_id' => $contentIndex->getId()
            ]
        );

        $resConnections = $sqlConnections->run();

        // Delete content index
        $sql = new Delete(
            'ukm_search_as_content_index',
            [
                'index_id' => $contentIndex->getId()
            ]
        );

        $res = $sql->run();

        return $res && $resConnections;
    }

    public static function createSearchLog(string $searchText, string $contextId, int $userId) : int {
        $sql = new Insert('ukm_search_as_searched_query');
        $sql->add('query_text', $searchText);
        $sql->add('user_id', $userId);
        $sql->add('context_id', $contextId);
        $sql->add('created_at', date('Y-m-d H:i:s', time()));
        
        $res = $sql->run();

        return $res;
    }

    public static function clickedResult($queryId, $indexId, $text = null) {
        // Check if the userId is the same
        $sql = new Query(
            "SELECT user_id, clicked_index_id FROM ukm_search_as_searched_query WHERE query_id = '#id'",
            [
                'id' => $queryId
            ]
        );
        
        $res = $sql->run('array');

        // Result is not found or the index has already been clicked
        if(!$res || $res['clicked_index_id'] != null) {
            return false;
        }
        // User is not the same
        if(!$res['user_id'] || $res['user_id'] != get_current_user_id()) {
            return false;
        }
        
        // Update the clicked index
        $updateQuery = new Update(
            'ukm_search_as_searched_query',
            [
                'query_id' => $queryId
            ]
        );
        
        // If it has text, use text, else use indexId
        if($text != null) {
            $updateQuery->add('clicked_text', $text);
        } else {
            $updateQuery->add('clicked_index_id', $indexId);
        }

        $res = $updateQuery->run();
    }
}