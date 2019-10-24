<?php

namespace UKMNorge\API\Mailchimp;
use Exception;

abstract class Liste {
    public static $audience;

    public static function init(){}

    /**
     * Tag given Subscriber with list of tags
     *
     * @param Subscriber $subscriber
     * @param Array<String> $tags
     * @return Result 
     */
    public static function addTags( Subscriber $subscriber, Array $tags ) {
        static::init();
        $tag_objects = [];
        foreach( $tags as $tag_name ) {
            $tag_objects[] = static::_getAudience()->getTags()->get( $tag_name );
        }
        return $subscriber->addTags( $tag_objects );
    }

    /**
     * Tag given Subscriber with Tag
     *
     * @param Subscriber $subscriber
     * @param String $tag
     * @return Bool true
     */
    public static function tag( Subscriber $subscriber, String $tag ) {
        static::init();
        $tag = static::_getAudience()->getTags()->get( $tag );
        $subscriber->addTag( $tag );
        return true;    
    }

    /**
     * Remove Tag from given Subscriber
     *
     * @param Subscriber $subscriber
     * @param String $tag
     * @return Bool true
     */
    public static function unTag( Subscriber $subscriber, String $tag ) {
        static::init();
        $tag = static::_getAudience()->getTags()->get( $tag );
        $subscriber->unTag( $tag );
        return true;    
    }

    /**
     * Subscribe given subscriber to this Audience
     *
     * @param Subscriber $subscriber
     * @return Bool true
     */
    public static function subscribe( Subscriber $subscriber ) {
        static::init();
        static::_getAudience()->subscribe( $subscriber );
        static::_getAudience()->persist();
        return true;
    }

    /**
     * Subscribe given subscriber to this Audience
     *
     * @param Subscriber $subscriber
     * @return Bool true
     */
    public static function unSubscribe( Subscriber $subscriber ) {
        static::init();
        static::_getAudience()->unSubscribe( $subscriber );
        static::_getAudience()->persist();
        return true;
    }

    /**
     * Get current audience
     *
     * @return Audience
     */
    private static function _getAudience() {
        if( static::$audience == null ) {
            throw new Exception(
                'Missing audience',
                582010
            );
        }
        return static::$audience;
    }
}