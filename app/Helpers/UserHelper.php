<?php

namespace App\Helpers;

use App\Models\SystemInfo;

class UserHelper
{
	public static function currentUser()
	{
		return app("current_user");
	}
}