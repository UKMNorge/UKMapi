<?php

namespace UKMNorge\Filmer\UKMTV\Server;

use UKMNorge\Database\SQL\Query;

class Server extends BandwidthMode
{
    const STORAGE_BASEPATH = 'ukmno/videos/';
    static $cache = null;

    /**
     * Hent en aktiv cache-server, eller videostorage
     * 
     * Siden vi alltid skal jobbe mot én og samme server
     * for hver request, mellomlagres det i singleton.
     *
     * @param Bool $skipProtocol - hvis du ikke vil ha https først
     * @return String 
     */
    public static function getCacheUrl($skipProtocol = false)
    {
        if (null == static::$cache) {
            $sql = new Query(
                "SELECT `ip`
                FROM `ukm_tv_caches_caches`
                WHERE `last_heartbeat` >= NOW() - INTERVAL 3 MINUTE
                    AND `status` = 'ok' AND `deactivated` = 0
                ORDER BY RAND()
                LIMIT 1"
            );
            $server = $sql->getField();
            if (!$server) {
                error_log('NO ACTIVE CACHE');
                static::$cache = static::getStorageUrl($skipProtocol);
            } else {
                static::$cache = $server;
            }
        }
        return ($skipProtocol ? '' : 'https://') . static::$cache . '/';
    }

    /**
     * Hent app-name for wowza
     * 
     * Burde være samme på tvers av alle cacher, men kan endres her,
     * skulle det dukke opp behov for det
     *
     * @return String
     */
    public static function getWowzaAppName()
    {
        return 'ukmtvhttp';
    }

    /**
     * Hent full URL til wowza-app, på en random cache
     * 
     * @param Bool $skipProtocol - hvis du ikke vil ha https først
     * @return String random cache wowza app
     */
    public static function getWowzaUrl($skipProtocol=false)
    {
        return static::getCacheUrl($skipProtocol) . static::getWowzaAppName() . '/_definst_/';
    }

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
    public static function getOembedUrl()
    {
        return static::_getServer('oembed');
    }

    /**
     * URL til UKM-TV lagringsserver
     *
     * @param Bool $skipProtocol - hvis du ikke vil ha https først
     * @return String med trailing slash
     */
    public static function getStorageUrl($skipProtocol = false)
    {
        return str_replace('ukm.dev', 'ukm.no', static::_getServer('video', $skipProtocol));
    }

    /**
     * Hent standarddomener for ukm.no
     *
     * @param String $domain
     * @param Bool $skipProtocol - hvis du ikke vil ha https først
     * @return String https://$domain.ukm.(no|dev)
     */
    private static function _getServer(String $domain, $skipProtocol = false)
    {
        return ($skipProtocol ? '' : 'https://') . $domain . '.' . UKM_HOSTNAME . '/';
    }
}
