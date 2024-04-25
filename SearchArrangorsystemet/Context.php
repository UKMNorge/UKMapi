<?php

namespace UKMNorge\SearchArrangorsystemet;

use UKMNorge\Database\SQL\Query;

require_once('UKM/Autoloader.php');

class Context {
    private $id;
    private $name;
    private $tableName = 'ukm_search_as_context';

    public function __construct(Int $id) {
        $this->_load($id);
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    private function getLoadQuery() {
        return "SELECT * FROM " . $this->tableName;
    }

    private function _load(Int $id) {
        $sql = new Query(
            $this->getLoadQuery() . "
            WHERE `id` = '#id'",
            [
                'id' => $id
            ]
        );
        $res = $sql->run('array');

        $this->id = $res['context_id'];
        $this->name = $res['context_name'];
    }
}