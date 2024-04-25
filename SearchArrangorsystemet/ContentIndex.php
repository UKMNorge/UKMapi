<?php

namespace UKMNorge\SearchArrangorsystemet;

use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');


class ContentIndex {
    private int $id;
    private string $siteUrl;
    private string $title;
    private string $description;
    private string|null $contentType;
    private string $context;

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

    public function getId(): int {
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

    public function getContext(): string {
        return $this->context;
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