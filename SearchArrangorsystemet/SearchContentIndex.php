<?php

namespace UKMNorge\SearchArrangorsystemet;
use UKMNorge\SearchArrangorsystemet\ContentIndex;


use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class SearchContentIndex {
    

    public static function search(string $searchText, int $searchContext) : array {
        $res = self::searchDB($searchText, $searchContext);
        return $res;

    }

    private static function generateKeywords(string $searchText) : string {
        // Split string by space and append like this k.keyword_name LIKE '%passo%' OR ...
        $keywords = explode(' ', $searchText);
        $keywordString = '';
        foreach($keywords as $keyword) {
            $keywordString .= "k.keyword_name LIKE '%$keyword%'";
        }
        return $keywordString;
    }

    
    private static function searchDB(string $searchText, int $searchContext) : array {
        $sql = new Query("
            SELECT c.index_id, c.site_url, c.title, c.description, c.content_type, c.context_id, SUM(ck.weight) AS relevance
            FROM ukm_search_as_content_index c
            JOIN ukm_search_as_content_keyword ck ON c.index_id = ck.index_id
            JOIN ukm_search_as_keyword k ON ck.keyword_id = k.keyword_id
            WHERE ". self::generateKeywords($searchText) ." AND c.context_id = '#context_id'
            GROUP BY c.index_id
            ORDER BY relevance DESC;",
            [
                'context_id' => $searchContext
            ]
        );
        $res = $sql->run();
        
        $retArr = [];

        foreach($res as $value) {
            $retArr[] = new ContentIndex($value['index_id'], $value['site_url'], $value['title'], $value['description'], $value['content_type'], $value['context_id']);
        }
        return $retArr;
    }
}