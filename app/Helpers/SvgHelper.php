<?php

namespace App\Helpers;

class SvgHelper
{
	public static function svg($name)
	{
		$path = resource_path("svg/{$name}.svg");
		if (file_exists($path)) {
			return file_get_contents($path);
		}
		return null;
	}
}