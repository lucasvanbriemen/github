<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Notification;
use App\Mail\NotificationOverview;
use App\GithubConfig;
use Illuminate\Support\Facades\Mail;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('completed', false)
            ->with([
                'triggeredBy:id,name',
                'comment.item.repository:id,full_name',
                'comment.author:id,display_name',
                'item.repository:id,full_name',
                'review.baseComment.item.repository:id,full_name',
                'review.baseComment.author:id,display_name'
            ])
            ->get(['id', 'type', 'completed', 'created_at', 'triggered_by_id', 'related_id']);

        foreach ($notifications as $notification) {
            $notification->subject = $notification->subject();
        }

        return response()->json($notifications);
    }

    public function show(Request $request, $id)
    {
        $notification = Notification::with(relations: 'triggeredBy')->findOrFail($id);
        $notification->loadRelatedData();

        return response()->json($notification);
    }

    public function complete(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->completed = true;
        $notification->save();

        return response()->json(['sucess' => true]);
    }

    public function digest(Request $request, $date)
    {
        $result = self::buildDigest(Notification::where('emailed_at', $date));
        return response()->json($result);
    }

    private static function buildDigest($query)
    {
        $notifications = $query->get();

        foreach ($notifications as $notification) {
            $notification->loadRelatedData();
            $notification->subject = $notification->subject();
        }

        // Group notifications by their parent item
        $itemGroups = [];
        $orphaned = [];

        foreach ($notifications as $notification) {
            $item = $notification->resolveItem();
            if ($item) {
                $itemGroups[$item->id][] = $notification;
            } else {
                $orphaned[] = $notification;
            }
        }

        // Link PRs with their closing-keyword-referenced issues
        $closingKeywords = ['Closes', 'Fixes', 'Resolves', 'Close', 'Fix', 'Resolve'];
        $linkedGroups = [];
        $prToIssueMap = [];

        foreach ($itemGroups as $itemId => $group) {
            $item = $group[0]->resolveItem();
            if ($item && $item->isPullRequest() && $item->body) {
                foreach ($closingKeywords as $keyword) {
                    if (preg_match_all("/\b$keyword\s+#(\d+)\b/i", $item->getRawOriginal('body'), $matches)) {
                        foreach ($matches[1] as $issueNumber) {
                            $issue = Item::where('number', $issueNumber)
                                ->where('repository_id', $item->repository_id)
                                ->first();
                            if ($issue) {
                                $prToIssueMap[$itemId] = $issue->id;
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        // Build final grouped structure: issue -> own notifications + linked PR notifications
        $processed = [];
        foreach ($itemGroups as $itemId => $group) {
            if (isset($prToIssueMap[$itemId])) {
                continue;
            }

            $item = $group[0]->resolveItem();
            $entry = [
                'item' => $item,
                'notifications' => $group,
                'linked' => [],
            ];

            foreach ($prToIssueMap as $prItemId => $issueId) {
                if ($issueId === $itemId && isset($itemGroups[$prItemId])) {
                    $entry['linked'][] = [
                        'item' => $itemGroups[$prItemId][0]->resolveItem(),
                        'notifications' => $itemGroups[$prItemId],
                    ];
                    $processed[] = $prItemId;
                }
            }

            $linkedGroups[] = $entry;
            $processed[] = $itemId;
        }

        // PRs that link to issues not in our notification set
        foreach ($prToIssueMap as $prItemId => $issueId) {
            if (!in_array($prItemId, $processed)) {
                $issue = Item::with('repository')->find($issueId);
                $linkedGroups[] = [
                    'item' => $issue,
                    'notifications' => [],
                    'linked' => [[
                        'item' => $itemGroups[$prItemId][0]->resolveItem(),
                        'notifications' => $itemGroups[$prItemId],
                    ]],
                ];
            }
        }

        // Remaining ungrouped item groups (PRs without issue links)
        foreach ($itemGroups as $itemId => $group) {
            if (!in_array($itemId, $processed) && !isset($prToIssueMap[$itemId])) {
                $linkedGroups[] = [
                    'item' => $group[0]->resolveItem(),
                    'notifications' => $group,
                    'linked' => [],
                ];
            }
        }

        return [
            'groups' => $linkedGroups,
            'orphaned' => $orphaned,
        ];
    }

    public static function sendOverview()
    {
        $today = now()->toDateString();

        $ids = Notification::whereNull('emailed_at')
            ->where('completed', false)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        Notification::whereIn('id', $ids)->update(['emailed_at' => $today]);

        Mail::to(GithubConfig::USER_EMAIL)->send(new NotificationOverview($today));
    }
}
