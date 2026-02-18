<?php

namespace UKMNorge\Wordpress;

use UKMNorge\Database\SQL\Delete;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Arrangement\Arrangement;

use DateTime;

class Innlegg extends Nyhet {
    private array $data;
    private int $id;
    private int $authorId;
    private string $title;
    private string $content;
    private string $excerpt;
    private string $status;
    private string $slug;
    private string $type;
    private string $guid;
    private int $commentCount;
    private int $parentId;
    private int $menuOrder;
    private DateTime $date;
    private DateTime $modifiedDate;
    private array $categoryIds;
    private array $categoryNames;
    private array $categorySlugs;
    private ?int $thumbnailId;
    private ?string $thumbnailUrl;
    private ?string $thumbnailFile;
    private string $blogPath;

    private int $blog_id;
    private Arrangement|null $arrangement = null;

    public function __construct(array $postData, int $blog_id, Arrangement $arrangement = null) {
        parent::__construct($blog_id, $postData['ID']);

        $this->blog_id = $blog_id;
        $this->data = $postData;
        $this->id = (int) $postData['ID'];
        $this->authorId = (int) ($postData['post_author'] ?? 0);
        $this->title = (string) ($postData['post_title'] ?? '');
        $this->content = (string) ($postData['post_content'] ?? '');
        $this->excerpt = (string) ($postData['post_excerpt'] ?? '');
        $this->status = (string) ($postData['post_status'] ?? '');
        $this->slug = (string) ($postData['post_name'] ?? '');
        $this->type = (string) ($postData['post_type'] ?? '');
        $this->guid = (string) ($postData['guid'] ?? '');
        $this->commentCount = (int) ($postData['comment_count'] ?? 0);
        $this->parentId = (int) ($postData['post_parent'] ?? 0);
        $this->menuOrder = (int) ($postData['menu_order'] ?? 0);
        $this->date = new DateTime($postData['post_date'] ?? 'now');
        $this->modifiedDate = new DateTime($postData['post_modified'] ?? 'now');
        $this->categoryIds = $this->parseCategoryIds($postData['category_ids'] ?? '');
        $this->categoryNames = $this->parseCategoryValues($postData['category_names'] ?? '');
        $this->categorySlugs = $this->parseCategoryValues($postData['category_slugs'] ?? '');
        $this->thumbnailId = isset($postData['thumbnail_id']) && is_numeric($postData['thumbnail_id'])
            ? (int) $postData['thumbnail_id']
            : null;
        $this->thumbnailFile = isset($postData['thumbnail_file']) && $postData['thumbnail_file'] !== ''
            ? (string) $postData['thumbnail_file']
            : null;
        $this->blogPath = isset($postData['blog_path']) ? (string) $postData['blog_path'] : '';
        $this->thumbnailUrl = $this->buildThumbnailUrl($this->blogPath, $this->thumbnailFile);
        $this->arrangement = $arrangement;
    }

    public function getId(): int {
        return $this->id;
    }

    public function getAuthorId(): int {
        return $this->authorId;
    }

    public function getTitle(): string {
        if (strlen($this->title) < 1 || empty($this->title)) {
            return 'Innlegg';
        }
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

    public function getSlug(): string {
        return $this->slug;
    }

    public function getType(): string {
        return $this->type;
    }

    public function getGuid(): string {
        return $this->guid;
    }

    public function getCommentCount(): int {
        return $this->commentCount;
    }

    public function getParentId(): int {
        return $this->parentId;
    }

    public function getMenuOrder(): int {
        return $this->menuOrder;
    }

    public function getDate(): DateTime {
        return $this->date;
    }

    public function getModifiedDate(): DateTime {
        return $this->modifiedDate;
    }

    public function getCategoryIds(): array {
        return $this->categoryIds;
    }

    public function getCategoryNames(): array {
        return $this->categoryNames;
    }

    public function getCategorySlugs(): array {
        return $this->categorySlugs;
    }

    public function getThumbnailId(): ?int {
        return $this->thumbnailId;
    }

    public function getThumbnailUrl(): ?string {
        return $this->thumbnailUrl;
    }

    public function getBlogId(): int {
        return $this->blog_id;
    }

    public function getArrangement(): Arrangement|null {
        return $this->arrangement;
    }

    public function getRaw(string $key, mixed $default = null): mixed {
        return $this->data[$key] ?? $default;
    }

    private function buildThumbnailUrl(string $blogPath, ?string $thumbnailFile): ?string {
        if ($thumbnailFile === null || $thumbnailFile === '') {
            return null;
        }

        $path = $blogPath !== '' ? $blogPath : '/';
        if ($path[0] !== '/') {
            $path = '/'.$path;
        }
        if (substr($path, -1) !== '/') {
            $path .= '/';
        }

        return 'https://'. UKM_HOSTNAME . $path . 'wp-content/uploads/sites/' . $this->blog_id . '/' . ltrim($thumbnailFile, '/');
    }

    private function parseCategoryIds(string $value): array {
        if (trim($value) === '') {
            return [];
        }
        $ids = array_map('intval', explode(',', $value));
        return array_values(array_filter($ids, static fn($id) => $id > 0));
    }

    private function parseCategoryValues(string $value): array {
        if (trim($value) === '') {
            return [];
        }
        $values = array_map('trim', explode(',', $value));
        $values = array_filter($values, static fn($val) => $val !== '');
        return array_values($values);
    }
}
