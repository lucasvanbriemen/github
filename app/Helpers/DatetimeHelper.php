<?php

namespace App\Helpers;

use Carbon\Carbon;

class DatetimeHelper
{
	public static function timeAgo ($datetime) {
		$now = new Carbon();
		$past = new Carbon($datetime);
		$interval = $now->diff($past);

		if ($interval->y > 0) {
			return $interval->y . ' year' . ($interval->y > 1 ? 's' : '') . ' ago';
		} elseif ($interval->m > 0) {
			return $interval->m . ' month' . ($interval->m > 1 ? 's' : '') . ' ago';
		} elseif ($interval->d > 0) {
			return $interval->d . ' day' . ($interval->d > 1 ? 's' : '') . ' ago';
		} elseif ($interval->h > 0) {
			return $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
		} elseif ($interval->i > 0) {
			return $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
		} else {
			return 'just now';
		}
	}
}
