<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Inertia\Inertia;

class NotificationController extends Controller
{
    public function index(Request $r)
    {
        $u = $r->user();

        return Inertia::render('Notifications/Index', ['notifications' => $u->notifications()->paginate(15), 'preferences' => NotificationPreference::firstOrCreate(['user_id' => $u->id])]);
    }

    public function read(Request $r, string $notification)
    {
        $item = $r->user()->notifications()->whereKey($notification)->firstOrFail();
        $item->markAsRead();
        $url = $item->data['url'] ?? route('notifications.index');

        return redirect(str_starts_with($url, '/') ? $url : route('notifications.index'));
    }

    public function readAll(Request $r)
    {
        $r->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'All notifications marked as read.');
    }

    public function destroy(Request $r, string $notification)
    {
        $r->user()->notifications()->whereKey($notification)->firstOrFail()->delete();

        return back()->with('success', 'Notification deleted.');
    }

    public function preferences(Request $r)
    {
        $data = $r->validate(['in_app' => 'required|boolean', 'email' => 'required|boolean', 'application_updates' => 'required|boolean', 'job_alerts' => 'required|boolean', 'interview_reminders' => 'required|boolean']);
        NotificationPreference::updateOrCreate(['user_id' => $r->user()->id], $data);

        return back()->with('success', 'Notification preferences saved.');
    }
}
