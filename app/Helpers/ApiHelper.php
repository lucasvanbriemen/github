<?php

namespace App\Helpers;

use App\Models\SystemInfo;

class ApiHelper
{
    public static $base = 'https://api.github.com';

    public static $token;

    public static $headers = [];

    public static function init()
    {
        self::$token = config('services.github.access_token');
        self::$headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.self::$token,
            'Github-Api-Version' => '2022-11-28',
            'User-Agent' => 'github-gui',
        ];
    }

    public static function githubApi($route)
    {
        self::init();
        self::updateSystemInfo(self::$base.$route);

        $fullUrl = self::$base.$route;

        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, self::formatHeaders());
        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($responseBody);
        } else {
            return null;
        }
    }

    public static function githubGraphql(string $query, array $variables = [])
    {
        self::init();

        $ch = curl_init('https://api.github.com/graphql');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $headers = self::formatHeaders();
        // GraphQL requires JSON accept
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'query' => $query,
            'variables' => (object) $variables,
        ]));

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            return json_decode($responseBody);
        }
        return null;
    }

    private static function updateSystemInfo($url)
    {
        $systemInfo = new SystemInfo([
            'api_url' => $url,
            'expires_at' => now()->addHours(1),
        ]);
        $systemInfo->save();
    }

    private static function formatHeaders()
    {
        $formatted = [];
        foreach (self::$headers as $key => $value) {
            $formatted[] = $key.': '.$value;
        }

        return $formatted;
    }
}
