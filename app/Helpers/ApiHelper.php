<?php

namespace App\Helpers;

class ApiHelper
{
	public static $base = "https://api.github.com";
	public static $token;
	public static $headers = [];

	public static function init()
	{
		self::$token = config("services.github.access_token");
		self::$headers = [
			"Accept" => "application/json",
			"Authorization" => "Bearer " . self::$token,
			"Github-Api-Version" => "2022-11-28",
			"User-Agent" => "github-gui",
		];
	}

	public static function githubApi($route)
	{
    self::init();
    $fullUrl = self::$base . $route;

    $ch = curl_init($fullUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, self::formatHeaders());
    curl_setopt($ch, CURLOPT_HEADER, true); // include headers in output

    $response = curl_exec($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    $header = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize);

    // Parse headers
    $parsedHeaders = [];
    foreach (explode("\r\n", $header) as $line) {
        if (strpos($line, ":") !== false) {
            [$key, $value] = explode(":", $line, 2);
            $parsedHeaders[trim($key)] = trim($value);
        }
    }

    $decodedBody = json_decode($body, true); // decode as associative array
    if (!is_array($decodedBody)) {
        $decodedBody = []; // fallback if body is empty or not JSON
    }

    $decodedBody['headers'] = $parsedHeaders; // inject headers into body

    return (object)$decodedBody;
	}

	private static function formatHeaders()
	{
		$formatted = [];
		foreach (self::$headers as $key => $value) {
			$formatted[] = $key . ": " . $value;
		}
		return $formatted;
	}
}