<?php

namespace App\Helpers;

use App\Models\SystemInfo;

class IssueHelper
{

	public static $allowedTimelineEvents = [
		'closed',
		'reopened',
		'referenced',
		'committed',
		'merged',
		'head_ref_deleted',
		'head_ref_restored',
		'assigned',
		'unassigned',
		'labeled',
		'unlabeled',
		'milestoned',
		'demilestoned',
	];

	public static function timelineView($event, $data, $actor) {
		// Return view based on event type (e.g. timeline/closed, timeline/reopened, etc.)
		$viewName = "timeline." . str_replace('_', '-', $event->event);
		if (view()->exists($viewName)) {
			return view($viewName, ['data' => $data, 'actor' => $actor, 'event' => $event])->render();
		}
		return "";
	}
}