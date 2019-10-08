CREATE TABLE `arrangor_news_comment` (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `blog_id` int(11) NOT NULL,
 `post_id` int(11) NOT NULL,
 `user_id` int(11) NOT NULL,
 `user_name` varchar(50) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
 `comment` text CHARACTER SET utf8mb4 NOT NULL,
 `ip` varchar(255) CHARACTER SET utf8mb4 NOT NULL DEFAULT '',
 `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `arrangor_news_like` (
 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
 `blog_id` int(11) NOT NULL,
 `post_id` int(11) NOT NULL,
 `user_id` int(11) NOT NULL,
 PRIMARY KEY (`id`),
 UNIQUE KEY `one_like_per_user_per_post` (`blog_id`,`post_id`,`user_id`),
 KEY `blog_id` (`blog_id`),
 KEY `post_id` (`post_id`),
 KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;