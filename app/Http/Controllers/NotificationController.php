<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $this->authenticatedUser($request);

        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json(['notifications' => $notifications]);
    }

    public function markAsRead(Request $request, int $id)
    {
        $user = $this->authenticatedUser($request);

        $notification = Notification::where('user_id', $user->id)->find($id);
        if (!$notification) {
            return response()->json(['message' => 'Notificação não encontrada'], 404);
        }

        $notification->is_read = true;
        $notification->save();

        return response()->json(['notification' => $notification]);
    }
}
