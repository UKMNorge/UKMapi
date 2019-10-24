<?php

namespace UKMNorge\API\Mailchimp\Liste;

use Exception;
use UKMNorge\API\Mailchimp\Liste;
use UKMNorge\API\Mailchimp\Mailchimp;

/**
 * 
 * USAGE:
 * Arrangor::tag( $marius, 'test_'. date('H-i') );
 * Arrangor::unTag( $marius, 'test_'. date('H-i') );
 * Arrangor::subscribe( $marius );
 * Arrangor::unSubscribe( $marius );
 * $audience = Arrangor::getListe();
 */

class Arrangor extends Liste {
    public static function init() 
    {
        if( null == static::$audience ) {
            if( !defined('MAILCHIMP_LIST_ID_ARRANGOR') ) {
                throw new Exception(
                    'Kan ikke hente arrangør-listen fra mailchimp, da ID mangler.',
                    582001
                );
            }
            static::$audience = Mailchimp::getAudiences()->get( MAILCHIMP_LIST_ID_ARRANGOR );
        }
    }
}