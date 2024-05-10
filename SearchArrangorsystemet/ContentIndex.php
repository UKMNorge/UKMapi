<?php

namespace UKMNorge\SearchArrangorsystemet;

use Exception;
use UKMNorge\SearchArrangorsystemet\Keyword;

use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');


class ContentIndex {
    private string $id;
    private string $siteUrl;
    private string $title;
    private string $description;
    private string|null $contentType;
    private string $context;
    private array $keywords = [];

    private static string $tableName = 'ukm_search_as_content_index';

    public function __construct(string $id, string $siteUrl, string $title, string $description, string|null $contentType, string $context) {
        $this->id = $id;
        $this->siteUrl = $siteUrl;
        $this->title = $title;
        $this->description = $description;
        $this->contentType = $contentType;
        $this->context = $context;
    }
    
    public static function getById($id) : ContentIndex {
        return self::_load($id);
    }

    public static function getAllWithKeywords() {
        $sql = new Query("
            SELECT 
                ci.index_id,
                ci.site_url,
                ci.title,
                ci.description,
                ci.content_type,
                ci.context_id,
                GROUP_CONCAT(kw.keyword_id ORDER BY ck.weight DESC SEPARATOR ',') AS keywords
            FROM 
                ukm_search_as_content_index ci
            LEFT JOIN 
                ukm_search_as_content_keyword ck ON ci.index_id = ck.index_id
            LEFT JOIN 
                ukm_search_as_keyword kw ON ck.keyword_id = kw.keyword_id
            GROUP BY 
                ci.index_id   
        ");
        $res = $sql->run();
        $retArr = [];
        foreach($res as $value) {
            $contentIndex = new ContentIndex($value['index_id'], $value['site_url'], $value['title'], $value['description'], $value['content_type'], $value['context_id']);
            $retArr[] = $contentIndex;
            
            // Generate keywords
            if($value['keywords']) {
                foreach(explode(',', $value['keywords']) as $kwId) {
                    $contentIndex->addKeyword(Keyword::getById($kwId));
                }
            }
        }
        return $retArr;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getSiteUrl(): string {
        return $this->siteUrl;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function getContentType(): string {
        return $this->contentType;
    }

    public function getContextId(): string {
        return $this->context;
    }

    public function getKeywords() : array {
        return $this->keywords;
    }

    public function addKeyword(Keyword $keyword) {
        $this->keywords[] = $keyword;
    }

    private static function getLoadQuery() : string {
        return "SELECT * FROM " . self::$tableName;
    }

    private static function _load( Int $id ) : ContentIndex {
		$sql = new Query(
            self::getLoadQuery()."
            WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );
		$res = $sql->run('array');
		
        
        $id = $res['index_id'];
        $siteUrl = $res['site_url'];
        $title = $res['title'];
        $description = $res['description'];
        $contentType = $res['content_type'];
        $context = $res['context_id'];

        $contentIndex = new ContentIndex($id, $siteUrl, $title, $description, $contentType, $context);

        return $contentIndex;
    }
}