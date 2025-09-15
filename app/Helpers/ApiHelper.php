<?php

namespace App\Helpers;

use App\Models\SystemInfo;

class ApiHelper
{

	public static $MAX_CALLS_PER_HOUR = 5000;

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
		self::updateSystemInfo(self::$base . $route);

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

	public static function githubApiPaginated($route, $perPage = 100)
	{
		$allData = [];
		$page = 1;

		do {
			$separator = strpos($route, '?') !== false ? '&' : '?';
			$paginatedRoute = $route . $separator . "page={$page}&per_page={$perPage}";

			$pageData = self::githubApi($paginatedRoute);

			if (!$pageData || !is_array($pageData)) {
				break;
			}

			$allData = array_merge($allData, $pageData);
			$page++;

		} while (count($pageData) == $perPage);

		return $allData;
	}

	private static function updateSystemInfo($url)
	{
		$systemInfo = new SystemInfo([
				"api_url" => $url,
				"expires_at" => now()->addHours(1),
		]);
		$systemInfo->save();
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