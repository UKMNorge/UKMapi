<?php

namespace UKMNorge\Wordpress;

use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Arrangement\Arrangement;

use DateTime;

class Infosak extends Nyhet {
    private $id;
    private $title;
    private $content;
    private $excerpt;
    private $status;
    private $modifiedDate;
    private $type;

    private $blog_id;
    private Arrangement|null $arrangement = null;

    public function __construct(array $postData, int $blog_id, Arrangement $arrangement = null) {
        parent::__construct($blog_id, $postData['ID']);
        $this->id = $postData['ID'];
        $this->title = $postData['post_title'];
        $this->content = $postData['post_content'];
        $this->excerpt = $postData['post_excerpt'];
        $this->status = $postData['post_status'];
        $this->modifiedDate = new DateTime($postData['post_modified']);
        $this->type = $postData['post_type'];

        $this->blog_id = $blog_id;
        $this->arrangement = $arrangement;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getTitle(): string {
        return $this->title;
    }

    public function getContent(): string {
        return $this->content;
    }

    public function getExcerpt(): string {
        return $this->excerpt;
    }

    public function getStatus(): string {
        return $this->status;
    }

    public function getModifiedDate(): DateTime {
        return $this->modifiedDate;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getBlogId(): int {
        return $this->blog_id;
    }

    public function getArrangement(): Arrangement|null {
        return $this->arrangement;
    }

    public function getDeltaLenke($arrangementId): string {
        return 'https://delta.'. UKM_HOSTNAME .'/public/infosak/' . $arrangementId . '/' . $this->getId() . '/';
    }
}