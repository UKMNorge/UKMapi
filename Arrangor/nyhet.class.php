<?php

namespace UKMNorge\Arrangor;

use SQL;
use SQLins;
use SQLdel;
use DateTime;

class Nyhet {
    private $blog_id;
    private $post_id;

    private $like_count = null;
    private $has_liked = null;

    private $comment_count = null;
    private $comments = null;

    public function __construct( $blog_id, $post_id ) {
        $this->blog_id = $blog_id;
        $this->post_id = $post_id;
    }

    public function getId() {
        return $this->getBlogId() .'-'. $this->getPostId();
    }

    public function getBlogId() {
        return $this->blog_id;
    }
    public function getPostId() {
        return $this->post_id;
    }

    public function getLikeCount() {
        if( null == $this->like_count ) {
            $this->_loadLikeCount();
        }
        return $this->like_count;
    }
    public function hasLiked( $user_id ) {
        if( is_array( $this->has_liked ) && isset( $this->has_liked[ $user_id ] ) ) {
            return $this->has_liked[ $user_id ];
        }
        return $this->_loadHasLiked( $user_id );
    }

    private function _loadLikeCount() {
        $sql = new SQL("
            SELECT COUNT(`id`) AS `likes` 
            FROM `arrangor_news_like`
            WHERE `blog_id` = '#blog_id'
            AND `post_id` = '#post_id'",
            [
                'blog_id' => $this->getBlogId(),
                'post_id' => $this->getPostId(),
            ]
        );

        $res = $sql->run('field', 'likes');
        $this->like_count = is_numeric( $res ) ? (int) $res : 0;
    }

    private function _loadHasLiked( $user_id ) {
        if( null == $this->has_liked ) {
            $this->has_liked = [];
        }

        $sql = new SQL("
            SELECT COUNT(`id`) AS `likes` 
            FROM `arrangor_news_like`
            WHERE `blog_id` = '#blog_id'
            AND `post_id` = '#post_id'
            AND `user_id` = '#user_id'
            ",
            [
                'blog_id' => $this->getBlogId(),
                'post_id' => $this->getPostId(),
                'user_id' => $user_id
            ]
        );
        $res = $sql->run('field', 'likes');
        $this->has_liked[ $user_id ] = is_numeric( $res ) && $res > 0;
        return $this->has_liked[ $user_id ];
    }

    public function doLike( $current_user ) {
        $sql = new SQLins('arrangor_news_like');
        $sql->add('blog_id', $this->getBlogId());
        $sql->add('post_id', $this->getPostId());
        $sql->add('user_id', $current_user);

        $res = $sql->run();

        return $res !== -1;
    }

    public function doDislike( $current_user ) {
        $sql = new SQLdel(
            'arrangor_news_like',
            [
                'blog_id' => $this->getBlogId(),
                'post_id' => $this->getPostId(),
                'user_id' => $current_user
            ]
        );
        $res = $sql->run();

        return $res == 1;
    }



    public function getCommenters() {
        $has = [];
        $commenters = [];
        foreach( $this->getComments() as $comment ) {
            if( !in_array( $comment->getAuthorId(), $has ) ) {
                $commenters[] = [
                    'id' => $comment->getAuthorId(),
                    'username' => $comment->getAuthor(),
                    'name' => $comment->getAuthorNiceName()
                ];
                $has[] = $comment->getAuthorId();
            }
        }
        return $commenters;
    }

    public function getCommentCount() {
        if( null == $this->comment_count ) {
            $this->_loadCommentCount();
        }
        return $this->comment_count;
    }
    public function getComments() {
        if( null == $this->comments ) {
            $this->_loadComments();
        }
        return $this->comments;
    }

    public function doComment( $user_id, $user_name, $comment ) {
        $sql = new SQLins('arrangor_news_comment');
        $sql->add('blog_id', $this->getBlogId());
        $sql->add('post_id', $this->getPostId());
        $sql->add('user_id', $user_id);
        $sql->add('user_name', $user_name);
        $sql->add('comment', $comment);
        $sql->add('ip', $_SERVER['HTTP_CF_CONNECTING_IP']);
        $res = $sql->run();
    }

    public function deleteComment( $comment_id, $user_id ) {
        if( !is_array( $this->getcomments() ) ) {
            return false;
        }
        foreach( $this->getComments() as $comment ) {
            if( $comment->getId() == $comment_id ) {

                $sql = new SQLdel(
                    'arrangor_news_comment',
                    [
                        'blog_id' => $this->getBlogId(),
                        'post_id' => $this->getPostId(),
                        'user_id' => $user_id,
                        'id' => $comment_id
                    ]
                );
                $res = $sql->run();

                return $res > 0;
            }
        }
        return false;
    }

    private function _loadComments() {
        $sql = new SQL("
            SELECT * 
            FROM `arrangor_news_comment`
            WHERE `blog_id` = '#blog_id'
            AND `post_id` = '#post_id'
            ORDER BY `id` DESC",
            [
                'blog_id' => $this->getBlogId(),
                'post_id' => $this->getPostId(),
            ]
        );

        $this->comments = [];
        $res = $sql->run();
        while( $row = $sql->fetch( $res ) ) {
            $this->comments[] = new arrangor_news_comment( $row );
        }
    }

    private function _loadCommentCount() {
        $sql = new SQL("
            SELECT COUNT(`id`) AS `comments` 
            FROM `arrangor_news_comment`
            WHERE `blog_id` = '#blog_id'
            AND `post_id` = '#post_id'",
            [
                'blog_id' => $this->getBlogId(),
                'post_id' => $this->getPostId(),
            ]
        );

        $res = $sql->run('field', 'comments');
        $this->comment_count = is_numeric( $res ) ? (int) $res : 0;
    }
}

class arrangor_news_comment {
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