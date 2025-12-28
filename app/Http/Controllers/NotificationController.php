<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('completed', false)->get();

        foreach ($notifications as $notification) {
            if ($notification->type === 'comment_mention' || $notification->type === 'item_comment') {
                $notification->load('comment.item.repository');
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
