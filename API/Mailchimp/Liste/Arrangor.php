<?php

namespace UKMNorge\API\Mailchimp\Liste;

use Exception;
use UKMNorge\API\Mailchimp\Mailchimp;
use UKMNorge\API\Mailchimp\MCList;

class Arrangor  {
    public static $mailchimp;

    /**
     * Hent arrangør-lista
     *
     * @return MCList
     */
    public static function getList() {
        static::_init();
        if( !defined('MAILCHIMP_LIST_ID_ARRANGOR') ) {
            throw new Exception(
                'Kan ikke hente arrangør-listen fra mailchimp, da ID mangler.',
                582001
            );
        }
        return static::$mailchimp->getList( MAILCHIMP_LIST_ID_ARRANGOR );
    }

    /**
     * Tag en e-postadresse i listen
     *
     * @param String $email
     * @param Array<String> $tag
     * @return void
     */
    public static function tag( String $email, Array $tag ) {
        static::_init();

        static::$mailchimp->addTagsToSubscriber( static::getList(), $tag, $email );
    }

    /**
     * Initier klassen
     *
     * @return void
     */
    private static function _init() {
        if( null == static::$mailchimp ) {
            static::$mailchimp = new Mailchimp();
        }
    }
}