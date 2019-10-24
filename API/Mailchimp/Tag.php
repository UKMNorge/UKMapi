<?php

namespace UKMNorge\API\Mailchimp;

use Exception;
use UKMNorge\Database\SQL\Insert;
use UKMNorge\Database\SQL\Query;
use UKMNorge\Database\SQL\Update;

class Tag
{
    var $id;
    var $audience_id;
    var $mailchimp_tag_id;
    var $name;
    var $db_id;

    /**
     * Create a new instance. Use static methods instead!
     * @see createFromAPIdata, createFromDBdata
     *
     * @param String $audience_id
     * @param String $tag_id
     * @param String $tag_name
     */
    public function __construct(String $audience_id, String $tag_id, String $tag_name)
    {
        $this->id = static::sanitize($tag_name);
        $this->name = static::sanitize($tag_name);
        $this->audience_id = $audience_id;
        $this->mailchimp_tag_id = $tag_id;
    }

    /**
     * Create a tag instance based on API data
     * Auto-persist to DB
     *
     * @param stdClass $data
     * @return Tag
     */
    public static function createFromAPIdata($data)
    {
        $tag = new Tag(
            $data->list_id,
            $data->id,
            $data->name
        );
        $insertId = $tag->persist();

        if (empty($insertId)) {
            // If insert failed, but did not throw exception,
            // it is fairly safe to assume that it already is there.
            // Try fetching.
            $tag->setDbId(
                static::_getDbIdFromTagId($data->list_id, $data->id)
            );
        }
        return $tag;
    }

    /**
     * Create a tag instance based on database data
     *
     * @param Array $data
     * @return Tag
     */
    public static function createFromDBdata($data)
    {
        $tag = new Tag(
            $data['audience_id'],
            $data['mailchimp_id'],
            $data['name']
        );
        $tag->setDbId($data['id']);

        return $tag;
    }

    /**
     * Save tag to databae
     * Create/update automatically
     *
     * @throws Exception could not persist
     * @return Int $tag_db_id
     */
    public function persist()
    {
        if (is_null($this->getDbId())) {
            $type = 'insert';
            $sql = new Insert('mailchimp_tag');
        } else {
            $type = 'update';
            $sql = new Update('mailchimp_tag', ['id' => $this->getDbId()]);
        }
        $sql->add('audience_id', $this->getAudienceId());
        $sql->add('mailchimp_id', $this->getTagId());
        $sql->add('name', $this->getName());

        try {
            $res = $sql->run();
        } catch (Exception $e) {
            // if insert-error "duplicate key [audience_id+name]", setDbId(), and re-run persist.
            if ($e->getCode() != 901001) {
                $this->db_id = static::_getDbIdFromTagId(
                    $this->getAudienceId(),
                    $this->getTagId()
                );
                throw $e;
            }
        }

        if ($res === false) {
            throw new Exception(
                'Could not persist tag to database',
                582009
            );
        }
        return $type == 'insert' ? $res : $this->getId();
    }

    /**
     * Undocumented function
     *
     * @param String $audience_id
     * @param String $tag_id
     * @return Int db_id
     */
    private static function _getDbIdFromTagId(String $audience_id, String $tag_id)
    {
        $select = new Query(
            "SELECT `id`
            FROM `mailchimp_tag`
            WHERE `audience_id` = '#audience'
            AND `mailchimp_id` = '#tag_id'
            LIMIT 1",
            [
                'audience' => $audience_id,
                'tag_id' => $tag_id
            ]
        );
        $real_tag_id = $select->getField();

        if (!is_numeric($real_tag_id)) {
            throw new Exception(
                'Could not find tag ' . $tag_id . ' for audience ' . $audience_id
            );
        }

        return $real_tag_id;
    }

    /**
     * Creates a tag on a audience 
     *
     * @param String $audience_id
     * @param String $tag_name
     * @return Tag
     * @throws Exception malformed tag, Mailchimp request failed, Mailchimp tag create failed
     */
    public static function createTag(String $audience_id, String $tag_name)
    {
        $tag_name = static::sanitize($tag_name);
        Tag::validate($tag_name);

        $result = Mailchimp::sendPostRequest(
            '/lists/' . $audience_id . '/segments',
            [
                'name' => $tag_name,
                'static_segment' => []
            ]
        );

        if ($result->hasError()) {
            throw new Exception(
                'Could not create tag'
            );
        }

        if( $result->getData()->title == 'Bad Request' && strpos($result->getData()->detail, 'already exist') !== false ) {
            throw new Exception(
                'Cannot create already existing tag '. $tag_name
            );
        }

        $tag = new Tag(
            $audience_id,
            $result->getData()->id,
            $tag_name
        );
        $tag->persist();

        return $tag;
    }

    /**
     * Validate tag data
     *
     * @param String $tag
     * @return Bool true
     * @throws Exception malformed
     */
    public static function validate(String $tag)
    {
        if (empty($tag) || is_null($tag)) {
            throw new Exception(
                'Cannot create tag without name'
            );
        }
        return true;
    }

    /**
     * Sanitize tag name
     *
     * @param String $tag
     * @return String sanitized tag
     */
    public static function sanitize(String $tag)
    {
        return preg_replace(
            "/[^a-z0-9-]/",
            '',
            str_replace(
                ['æ', 'ø', 'å', 'ü', 'é', 'è'],
                ['a', 'o', 'a', 'u', 'e', 'e'],
                mb_strtolower($tag)
            )
        );
    }

    /**
     * Get the tag name
     * 
     * @return String
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the Audience ID
     *
     * @return String audience ID
     */
    public function getAudienceId()
    {
        return $this->audience_id;
    }

    /**
     * Get the value of name
     */
    public function getName()
    {
        return static::sanitize($this->name);
    }

    /**
     * Hent tagID (for bruk mot mailchimp)
     *
     * @return String
     */
    public function getTagId()
    {
        return $this->mailchimp_tag_id;
    }

    /**
     * Get database ID
     * 
     * @return Int $database id
     */
    public function getDbId()
    {
        return $this->db_id;
    }

    /**
     * Set database ID
     *
     * @param Int $db_id
     * @return self
     */
    public function setDbId(Int $db_id)
    {
        $this->db_id = $db_id;

        return $this;
    }
}
