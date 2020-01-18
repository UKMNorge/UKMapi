<?php

namespace UKMNorge\Filmer\UKMTV\Server;

class Server extends BandwidthMode
{
    const STORAGE_BASEPATH = 'ukmno/videos/';

    /**
     * URL til UKM-TV
     *
     * @return String med trailing slash
     */
    public static function getTvUrl()
    {
        return static::_getServer('tv');
    }

    /**
     * URL til UKM-TV Embed-domenet
     *
     * @return String med trailing slash
     */
    public static function getEmbedUrl()
    {
        return static::_getServer('embed');
    }

    /**
     * URL til Oembed-domenet
     *
     * @return String url med trailing slash
     */
    public static function getOembedUrl() {
        return static::_getServer('oembed');
    }

    /**
     * URL til UKM-TV lagringsserver
     *
     * @return String med trailing slash
     */
    public static function getStorageUrl()
    {
        return str_replace('ukm.dev','ukm.no',static::_getServer('video'));
    }

    /**
     * URL til UKM-TV cacheserver
     *
     * @return String med trailing slash
     */
    public static function getCacheUrl()
    {
        $sql = new Query(
            "SELECT `ip`
            FROM `ukm_tv_caches_caches`
            WHERE `last_heartbeat` >= NOW() - INTERVAL 3 MINUTE
                AND `status` = 'ok' AND `deactivated` = 0
            ORDER BY RAND()
            LIMIT 1"
        );
        return 'https://' . $sql->getField();
    }

    /**
     * Hent standarddomener for ukm.no
     *
     * @param String $domain
     * @return String https://$domain.ukm.(no|dev)
     */
    private static function _getServer(String $domain)
    {
        return 'https://' . $domain . '.' . UKM_HOSTNAME .'/';
    }
}
