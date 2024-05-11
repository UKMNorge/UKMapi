<?php

namespace UKMNorge\SearchArrangorsystemet;
use UKMNorge\SearchArrangorsystemet\ContentIndex;

use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class Keyword {
    private string $id;
    private string $name;
    private int $contentIndexId;
    private int $weight;

    public function __construct(string $id, string $name, int $weight) {
        $this->id = $id;
        $this->name = $name;
        $this->weight = $weight;
    }

    public static function getById(string $id, string $contentIndexId) : Keyword {
        return self::_load($id, $contentIndexId);
    }

    public function setWeight(int $weight) {
        $this->weight = $weight;
    }

    public function getWeight(): string {
        return $this->weight;
    }

    public function getId(): string {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public static function _load(string $kwId, int $contentIndexId) {
        $sql = new Query(
            "SELECT * FROM ukm_search_as_keyword WHERE keyword_id = '#id'",
            [
                'id' => $kwId
            ]
        );

        $weightSql = new Query(
            "SELECT weight FROM ukm_search_as_content_keyword WHERE keyword_id = '#id' AND index_id = '#index_id'",
            [
                'id' => $kwId,
                'index_id' => $contentIndexId
            ]
        );

        $weight = 0;
        $resWSql = $weightSql->run('array');

        if($resWSql) {
            $weight = $resWSql['weight'];
            $weight = (floatval($weight)*100);
        }



        $res = $sql->run('array');
        return new Keyword($res['keyword_id'], $res['keyword_name'], $weight);
    }
}