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
		ApiHelper::init();
		$fullUrl = self::$base . $route;

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

	private static function formatHeaders()
	{
		$formatted = [];
		foreach (self::$headers as $key => $value) {
			$formatted[] = $key . ": " . $value;
		}
		return $formatted;
	}
}