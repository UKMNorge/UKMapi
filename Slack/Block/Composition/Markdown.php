<?php


namespace UKMNorge\Slack\Block\Composition;

/**
 * Sub class of Text composition
 * 
 * Added for simpler type hinting
 * 
 * @see https://api.slack.com/reference/block-kit/composition-objects#text
 */
class Markdown extends Text
{
    const TYPE = 'mrkdwn';
}