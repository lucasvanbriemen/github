<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

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

        return response()->json($notifications);
    }

    public function show(Request $request, $id)
    {
        $notification = Notification::with('triggeredBy')->findOrFail($id);
        $this->loadRelatedData($notification);

        return response()->json($notification);
    }

    public function complete(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->completed = true;
        $notification->save();

        return response()->json(['sucess' => true]);
    }

    private function loadRelatedData(Notification $notification)
    {
        if ($notification->type === 'comment_mention' || $notification->type === 'item_comment') {
            $notification->load('comment.item.repository');
        }

        if ($notification->type === 'item_assigned' || $notification->type === 'review_requested') {
            $notification->load('item.repository');
        }

        if ($notification->type === 'pr_review') {
            $notification->load('review.baseComment.item.repository');
        }
    }
}
