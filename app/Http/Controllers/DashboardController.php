<?php

namespace App\Http\Controllers;
use App\Helpers\ApiResponse;
use App\Models\MemberProfile;
use App\Models\Event;
use App\Models\News;
use Carbon\Carbon;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $profile = MemberProfile::with(['unit','district','state'])
                    ->where('user_id', $user->id)->first();

        if (!$profile) {
            return ApiResponse::error('Profile not found.', 404);
        }
        if ($profile->status === 'ACTIVE'
            && $profile->expiry_date
            && Carbon::parse($profile->expiry_date)->isPast()) {
            $profile->update(['status' => 'EXPIRED']);
        }

        $registeredEventIds = \App\Models\EventRegistration::where('user_id', $user->id)
            ->pluck('event_id');

        $events = Event::where('status', 'UPCOMING')
            ->where('event_date', '>=', now())
            ->whereNotIn('id', $registeredEventIds)
            ->orderBy('event_date')
            ->limit(2)
            ->get();

        $events = $events->map(function ($event) {
            return [
                'id'               => (string) $event->id,
                'title'            => $event->title,
                'description'      => $event->description,
                'event_date'       => Carbon::parse($event->event_date)->format('d M Y'),
                'event_time'       => Carbon::parse($event->event_time)->format('h:i A'),

                'location'         => $event->is_online
                    ? ($event->location
                        ? $event->location . ', Online'
                        : 'Online')
                    : ($event->location ?? ''),

                'total_seats'      => (string) $event->total_seats,
                'registered_count' => (string) $event->registered_count,

                'remaining_seats'  => $event->total_seats == 0
    ? 'Unlimited'
    : (string) max(
        0,
        $event->total_seats - $event->registered_count
    ),

'registration_deadline' => $event->registration_deadline
    ? $event->registration_deadline->format('d M Y')
    : '',

'status' => $event->status,

                'image' => $event->image
                    ? asset('storage/' . $event->image)
                    : '',
            ];
        });

       $visibility = app(\App\Services\ContentVisibility::class);

$news = $visibility->news($user->id)
    ->latest('published_at')
    ->limit(3)
    ->get();

        $news = $news->map(function ($n) {
            return [
                'id' => (string) $n->id,

                'title' => $n->title,

                'content' => $n->content,

                'image' => $n->image
                    ? asset('storage/' . $n->image)
                    : '',

                'status' => $n->status,

                'published_at' => $n->published_at
                    ? $n->published_at->format('d M Y h:i A')
                    : '',

                'created_at' => (string) $n->created_at,
            ];
        });
        
       

        return ApiResponse::success([
            'member' => [
                'name'            => $user->name,
                'membership_id'   => $profile->membership_id ?? '',
                'status'          => $profile->status,
                'photo'           => $profile->photo
                                       ? asset('storage/'.$profile->photo)
                                       : '',
                'unit_name'       => $profile->unit->name ?? '',
                'district_name'   => $profile->district->name ?? '',
                'joined_date'     => $profile->joined_date
                                       ? Carbon::parse($profile->joined_date)->format('d M Y')
                                       : '',
            ],
            'upcoming_events' => $events,
    'latest_news'     => $news,
    'unread_counts'   => [
    'news'    => (string) $visibility->unreadNewsCount($user->id),
    'gallery' => (string) $visibility->unreadGalleryCount($user->id),
],
], 'Dashboard fetched successfully');
    }
}