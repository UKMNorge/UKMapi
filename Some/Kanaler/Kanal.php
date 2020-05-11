<?php

namespace UKMNorge\Some\Kanaler;

class Kanal
{

    const TABLE = 'some_kanal';

    public $id;
    public $navn;
    public $handlebar;
    public $url;
    public $emoji;
    public $emoji_kode;

    public function __construct(array $data)
    {
        $this->id = $data['id'];
        $this->navn = $data['navn'];
        $this->handlebar = $data['handlebar'];
        $this->url = $data['url'];
        $this->farge = '#'. $data['farge'];
        $this->emoji = $data['emoji'];
        $this->emoji_kode = $data['emojicode'];
    }

    /**
     * Array-representasjon av objektet
     *
     * @return Array
     */
    public function __toArray() {
        return [
            'id' => $this->getId(),
            'navn' => $this->getNavn(),
            'handlebar' => $this->getHandlebar(),
            'url' => $this->getUrl(),
            'farge' => $this->getFarge(),
            'emoji' => $this->getEmoji(),
            'emojikode' => $this->getEmojiKode()
        ];
    }

    /**
     * Hent kanalens Id
     *
     * @return String
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hent kanalens navn (brand name, ikke side-navn)
     *
     * @return String
     */
    public function getNavn()
    {
        return $this->navn;
    }

    /**
     * Hent kanalens handlebar (@ukmnorge)
     *
     * @return String
     */
    public function getHandlebar()
    {
        return $this->handlebar;
    }

    /**
     * Hent kanalens url
     *
     * @return String
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Hent fargekode (hex med prefix)
     *
     * @return String
     */
    public function getFarge() {
        return $this->farge;
    }

    /**
     * Hent kanalens emoji
     *
     * @return String unicode
     */
    public function getEmoji() {
        return $this->emoji;
    }

    /**
     * Hent kanalens emoji-kode
     *
     * @return String colon-format
     */
    public function getEmojiKode() {
        return $this->emoji_kode;
    }
}
