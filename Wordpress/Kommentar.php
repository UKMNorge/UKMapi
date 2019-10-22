<?php

namespace UKMNorge\Wordpress;

use DateTime;

class Kommentar {
    private $id = null;
    private $author = null;
    private $author_id = null;
    private $text = null;
    private $timestamp = null;

    public function __construct( $database_row ) {
        $this->id = $database_row['id'];
        $this->author = $database_row['user_name'];
        $this->author_id = $database_row['user_id'];
        $this->text = $database_row['comment'];
        $this->timestamp = new DateTime( $database_row['timestamp'] );
    }

    public function getAuthor() {
        return $this->author;
    }
    public function getAuthorId() {
        return $this->author_id;
    }
    public function getId() {
        return $this->id;
    }
    public function getText() {
        return $this->text;
    }
    public function getTimestamp() {
        return $this->timestamp;
    }
    public function getAuthorNiceName() {
        $userdata = get_userdata( $this->getAuthorId() );
        return $userdata->user_nicename;
    }
}