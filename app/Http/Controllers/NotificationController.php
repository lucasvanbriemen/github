<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('completed', false)
            ->with(['actor', 'repository'])
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($notifications as $notification) {
            switch ($notification->type) {
                case 'comment_mention':
                    $notification->load('comment.item.repository');
                    break;

                case 'assigned_to_item':
                case 'activity_on_assigned_item':
                    $notification->load('item.repository');
                    break;

                case 'workflow_failed':
                    $notification->load('workflow');
                    // Also load the PR from metadata
                    if (isset($notification->metadata['pr_id'])) {
                        $notification->pr = \App\Models\Item::with('repository')
                            ->find($notification->metadata['pr_id']);
                    }
                    break;
            }
        }

        return response()->json($notifications);
    }

    public function complete(Request $request, $id)
    {
        $notification = Notification::findOrFail($id);
        $notification->completed = true;
        $notification->save();

        return response()->json(['sucess' => true]);
    }
}
