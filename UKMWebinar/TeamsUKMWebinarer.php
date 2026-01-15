<?php

namespace UKMNorge\UKMWebinar;

use Exception;
use UKMNorge\Collection;

require_once('UKM/Autoloader.php');

const GRAPH_BASE = "https://graph.microsoft.com/v1.0";
const CACHE_FILE = '/var/cache/ukm' . "/webinars_cache.json";
const CACHE_TTL_SECONDS = 3600; // 1 time
// How many webinars to show
const MAX_TO_SHOW = 50;
// Include webinars that started recently (still “active”)
const ACTIVE_GRACE_MINUTES = 120;
// Optional: Only show webinars within next N days (null = no limit)
const MAX_DAYS_AHEAD = null; // e.g. 365
// Display timezone
const DISPLAY_TIMEZONE = "Europe/Oslo";

class TeamsUKMWebinarer extends Collection {
    protected $var = array();

    public function getAlleAcive() {
        $alle = $this->getAll();
        $aktive = [];
        foreach($alle as $webinar) {
            if($webinar->isActive()) {
                $aktive[] = $webinar;
            }
        }

        return $aktive;
    }

    public function getAll()
    {
        $this->_doLoad();
        return $this->var;
    }

    public function add($item)
    {
        // Denne må bruke find, og ikke har,
        // da har kjører doLoad, og doLoad kjører
        // add, som kjører har() (infinite loop, altså)
        if (!$this->find($item)) {
            $this->var[] = $item;
        }
        return $this;
    }

    private function _doLoad() {
        // Load cached data from file if available
        $cached = $this->loadCache(CACHE_FILE, CACHE_TTL_SECONDS);
        if (!$cached) {
            $token = $this->getAccessToken(TEAMS_TENANT_ID, TEAMS_CLIENT_ID, TEAMS_CLIENT_SECRET);
            $items = $this->fetchAllWebinars(GRAPH_BASE, $token, 500);

            

            $cached = [
                "fetchedAt" => gmdate("c"),
                "value" => $items
            ];
            $this->saveCache(CACHE_FILE, $cached);
        }

        // $token = $this->getAccessToken($tenantId, $clientId, $clientSecret);
        // $items = $this->fetchAllWebinars(GRAPH_BASE, $token, 500);
        
        $items = $cached["value"] ?? [];

        foreach ($items as $item) {
            $id = $item['id'] ?? '';
            $audience = $item['audience'] ?? '';
            $status = $item['status'] ?? '';
            $name = $item['displayName'] ?? '';
            $description = $item['description'] ? $item['description']['content'] : '';

            $startDateStr = $item['startDateTime'] ? $item['startDateTime']['dateTime'] : null;
            $endDateStr = $item['endDateTime'] ? $item['endDateTime']['dateTime'] : null;

            $startDate = $startDateStr ? new \DateTime($startDateStr) : null;
            $endDate = $endDateStr ? new \DateTime($endDateStr) : null;

            $webinar = new TeamsUKMWebinar(
                $id,
                $audience,
                $status,
                $name,
                $description,
                $startDate,
                $endDate
            );

            $this->add($webinar);
        }
    }

    private function getAccessToken(string $tenantId, string $clientId, string $clientSecret): string {
        $tokenUrl = "https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token";
        $data = $this->httpPostForm($tokenUrl, [
            "client_id" => $clientId,
            "client_secret" => $clientSecret,
            "scope" => "https://graph.microsoft.com/.default",
            "grant_type" => "client_credentials"
        ]);

        $token = $data["access_token"] ?? null;
        if (!$token || !is_string($token)) {
            throw new Exception("Could not obtain access_token from token response.");
        }
        return $token;
    }

    private function httpGetJson(string $url, string $accessToken): array {
        if (!function_exists('curl_init')) {
            throw new Exception("PHP cURL extension is not enabled. Enable it to use this script.");
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer {$accessToken}",
                "Accept: application/json"
            ],
            CURLOPT_TIMEOUT => 20,
        ]);

        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false) {
            throw new Exception("HTTP GET failed: {$err}");
        }
        if ($code < 200 || $code >= 300) {
            throw new Exception("HTTP GET error ({$code}): {$resp}");
        }

        $json = json_decode($resp, true);
        if (!is_array($json)) {
            throw new Exception("Failed to decode JSON response from Graph.");
        }
        return $json;
    }

    /**
     * Fetch all webinars (handles pagination via @odata.nextLink)
     */
    private function fetchAllWebinars(string $graphBase, string $accessToken, int $hardMax = 500): array
    {
        $url = $graphBase . "/solutions/virtualEvents/webinars?\$top=100";
        $all = [];

        while ($url) {
            $data = $this->httpGetJson($url, $accessToken);

            $items = $data["value"] ?? [];
            if (is_array($items)) {
                foreach ($items as $item) {
                    $all[] = $item;
                    if (count($all) >= $hardMax) {
                        $url = null;
                        break;
                    }
                }
            }

            $next = $data["@odata.nextLink"] ?? null;
            $url = (is_string($next) && $next !== "") ? $next : null;
        }

        return $all;
    }

    private function httpPostForm(string $url, array $fields): array {
        if (!function_exists('curl_init')) {
            throw new Exception("PHP cURL extension is not enabled. Enable it to use this script.");
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_HTTPHEADER => ["Content-Type: application/x-www-form-urlencoded"],
            CURLOPT_TIMEOUT => 20,
        ]);

        $resp = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($resp === false) {
            throw new Exception("HTTP POST failed: {$err}");
        }
        if ($code < 200 || $code >= 300) {
            throw new Exception("HTTP POST error ({$code}): {$resp}");
        }

        $json = json_decode($resp, true);
        if (!is_array($json)) {
            throw new Exception("Failed to decode JSON response from token endpoint.");
        }
        return $json;
    }

    private function loadCache(string $cacheFile, int $ttlSeconds): ?array {
        if (!file_exists($cacheFile)) return null;
        if ((time() - filemtime($cacheFile)) > $ttlSeconds) return null;

        $raw = file_get_contents($cacheFile);
        if ($raw === false) return null;

        $json = json_decode($raw, true);
        return is_array($json) ? $json : null;
    }

    private function saveCache(string $cacheFile, array $data): void {
        $tmp = $cacheFile;
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException(json_last_error_msg());
        }

        file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        // rename($tmp, $cacheFile);
    }
}