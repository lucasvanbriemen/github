<?php

namespace App\Helpers;

use App\Models\SystemInfo;

class UserHelper
{
	public static function currentUser()
	{
		return app("current_user");
	}

	public static function gravatar($user = null)
	{
		if (!$user) {
			$user = self::currentUser();
		}

		$hash = md5(strtolower(trim($user->email)));
		return "https://www.gravatar.com/avatar/{$hash}?s=200&d=identicon";
	}
}