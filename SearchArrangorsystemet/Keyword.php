<?php

namespace UKMNorge\SearchArrangorsystemet;
use UKMNorge\SearchArrangorsystemet\ContentIndex;

use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class Keyword {
    private string $id;
    private string $name;
    private int $weight;

    public function __construct(string $id, string $name) {
        $this->id = $id;
        $this->name = $name;
    }

    public static function getById(string $id) : Keyword {
        return self::_load($id);
    }

    public function getId(): string {
        return $this->id;
    }

    public function getName(): string {
        return $this->name;
    }

    public static function _load(string $id) {
        $sql = new Query(
            "SELECT * FROM ukm_search_as_keyword WHERE keyword_id = '#id'",
            [
                'id' => $id
            ]
        );
        $res = $sql->run('array');
        return new Keyword($res['keyword_id'], $res['keyword_name']);
    }
}