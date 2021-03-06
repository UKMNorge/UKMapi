<?php


namespace UKMNorge\Slack\Block\Composition;

/**
 * Sub class of Text composition
 * 
 * Added for simpler type hinting
 * 
 * @see https://api.slack.com/reference/block-kit/composition-objects#text
 */
class PlainText extends Text
{
    const TYPE = 'plain_text';
}