<?php

namespace App\Helpers;

class ApiHelper
{
    public const BASE_URL = 'https://api.github.com';

    public static $headers = [];

    public static function init()
    {
        self::$headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.config('services.github.access_token'),
            'Github-Api-Version' => '2022-11-28',
            'User-Agent' => 'github-gui',
        ];
    }

    public static function githubApi(string $route, string $method = 'GET', array $payload = null)
    {
        self::init();
        $fullUrl = self::BASE_URL . $route;

        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, self::formatHeaders());

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($payload) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            }
        }

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($responseBody);
        }

        return null;
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

    private static function formatHeaders()
    {
        $formatted = [];
        foreach (self::$headers as $key => $value) {
            $formatted[] = $key.': '.$value;
        }

        return $formatted;
    }
}
