<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Event;
use App\Models\DistrictAdmin;
use App\Models\UnitAdmin;
use App\Models\EventRegistration;
use App\Models\MemberProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EventController extends Controller
{
    // ─────────────────────────────────
    // GET EVENTS (Public + Optional Auth)
    // POST /api/events
    // ─────────────────────────────────
    
        // ─────────────────────────────────
    // HELPER: events visible to the current user
    // = global (super admin) + own district admin's + own unit admin's
    // Guests (no token) see global events only.
    // ─────────────────────────────────
    private function visibleEventsQuery()
    {
        $user    = auth('sanctum')->user();
        $profile = $user ? MemberProfile::where('user_id', $user->id)->first() : null;

        // Every district/unit admin (their events are NOT global)
        $scopedAdminIds = DistrictAdmin::pluck('user_id')
            ->merge(UnitAdmin::pluck('user_id'))
            ->unique()->values()->all();

        $myDistrictAdminIds = $profile
            ? DistrictAdmin::where('district_id', $profile->district_id)->pluck('user_id')->all()
            : [];

        $myUnitAdminIds = $profile
            ? UnitAdmin::where('unit_id', $profile->unit_id)->pluck('user_id')->all()
            : [];

        return Event::where(function ($q) use ($scopedAdminIds, $myDistrictAdminIds, $myUnitAdminIds) {
            $q->whereHas('createdBy', fn($u) => $u->whereNotIn('id', $scopedAdminIds))
              ->orWhereIn('created_by', $myDistrictAdminIds)
              ->orWhereIn('created_by', $myUnitAdminIds);
        });
    }
    
    
        public function getEvents(Request $request)
    {
        $request->validate([
            'tab'      => 'required|in:upcoming,registered,past',
            'search'   => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $tab      = $request->tab;
        $search   = $request->search;
        $perPage  = $request->per_page ?? 10;
        $user     = auth('sanctum')->user();

        Event::whereIn('status', ['UPCOMING', 'ONGOING'])
            ->where('event_date', '<', Carbon::today())
            ->update(['status' => 'COMPLETED']);

        if ($tab === 'registered') {
            if (!$user) return ApiResponse::error('Unauthenticated.', 401);

            $registrations      = EventRegistration::where('user_id', $user->id)->get();
            $registeredEventIds = $registrations->pluck('event_id');
            $ticketMap          = $registrations->pluck('ticket_number', 'event_id');

            // Not scoped on purpose: a member should always see events they already registered for
            $events = Event::whereIn('id', $registeredEventIds)
                ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%"))
                ->orderBy('event_date', 'desc')
                ->paginate($perPage);

            return $this->paginatedEventResponse($events, $registeredEventIds, $ticketMap);
        }

        if ($tab === 'upcoming') {
            $registeredEventIds = $user
                ? EventRegistration::where('user_id', $user->id)->pluck('event_id')
                : collect();

            $events = $this->visibleEventsQuery()
                ->whereIn('status', ['UPCOMING', 'ONGOING'])
                ->where('event_date', '>=', Carbon::today())
                ->when($user, fn($q) => $q->whereNotIn('id', $registeredEventIds))
                ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%"))
                ->orderBy('event_date', 'asc')
                ->paginate($perPage);

            return $this->paginatedEventResponse($events, $registeredEventIds);
        }

        if ($tab === 'past') {
            $events = $this->visibleEventsQuery()
                ->where(function ($q) {
                    $q->whereIn('status', ['COMPLETED', 'CANCELLED'])
                      ->orWhere(fn($q2) =>
                          $q2->whereIn('status', ['UPCOMING', 'ONGOING'])
                             ->where('event_date', '<', Carbon::today())
                      );
                })
                ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%"))
                ->orderBy('event_date', 'desc')
                ->paginate($perPage);

            $registeredEventIds = $user
                ? EventRegistration::where('user_id', $user->id)->pluck('event_id')
                : collect();

            return $this->paginatedEventResponse($events, $registeredEventIds);
        }
    }

private function paginatedEventResponse($paginator, $registeredEventIds, $ticketMap = null)
{
    $data = $paginator->getCollection()
        ->map(fn($e) => $this->formatEventCard($e, $registeredEventIds, $ticketMap));

    return ApiResponse::success([
        'data'         => $data,
        'current_page' => $paginator->currentPage(),
        'per_page'     => $paginator->perPage(),
        'total'        => $paginator->total(),
        'last_page'    => $paginator->lastPage(),
        'has_more'     => $paginator->hasMorePages(),
    ], 'Events fetched successfully');
}

private function formatEventCard($event, $registeredEventIds = null, $ticketMap = null)
{
    $data = [
        'id'               => (string) $event->id,
        'title'            => $event->title,
        'description'      => $event->description,
        'event_date'       => $event->event_date->format('d M Y'),
        'event_time'       => Carbon::parse($event->event_time)->format('h:i A'),
        'location'         => $event->location ?? '—',
        'ticket_type'      => 'FREE',
        'ticket_price'     => '0',
        'registered_count' => (string) $event->registered_count,
        'total_seats'      => (string) $event->total_seats,
        'remaining_seats'  => $event->total_seats == 0
    ? 'Unlimited'
    : (string) max(0, $event->total_seats - $event->registered_count),

'registration_deadline' => $event->registration_deadline
    ? $event->registration_deadline->format('d M Y')
    : '',

'status'           => $event->status,
        'image'            => $event->image
            ? asset('storage/' . $event->image)
            : '',
        'is_registered'    => $registeredEventIds
            ? $registeredEventIds->contains($event->id)
            : false,
    ];

    // Only include ticket_number when viewing registered tab
    if ($ticketMap && $ticketMap->has($event->id)) {
        $data['ticket_number'] = $ticketMap->get($event->id);
    }

    return $data;
}

    // ─────────────────────────────────
    // GET EVENT DETAIL (Public)
    // ─────────────────────────────────
        public function getEventDetail(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:events,id'
        ]);

        $event = $this->visibleEventsQuery()->where('id', $request->id)->first();

        if (!$event) {
            return ApiResponse::error('Event not found.', 404);
        }

        return ApiResponse::success(
            $this->formatEvent($event),
            'Event detail fetched successfully'
        );
    }

    // ─────────────────────────────────
    // GET ALL EVENTS (Super Admin)
    // ─────────────────────────────────
    public function getAllEvents()
    {
        $events = Event::with('createdBy')->latest()->get();

        return ApiResponse::success(
            $events->map(fn($e) => $this->formatEvent($e, true)),
            'Events fetched successfully'
        );
    }

    // ─────────────────────────────────
    // CREATE EVENT (Super Admin)
    // ─────────────────────────────────
    public function createEvent(Request $request)
    {
        $request->validate([
            'title'                 => 'required|string',
            'description'           => 'required|string',
            'event_date'            => 'required|date|after:today',
            'event_time'            => 'required',
            'location'              => 'nullable|string',
            'total_seats'           => 'required|integer|min:0',
            'registration_deadline' => 'nullable|date|before:event_date',
            'image'                 => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // is_online, online_link, ticket_type, ticket_price intentionally removed
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $fileName  = 'event_' . time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('event_images', $fileName, 'public');
        }

        $event = Event::create([
            'created_by'            => auth()->id(),
            'title'                 => $request->title,
            'description'           => $request->description,
            'image'                 => $imagePath,
            'event_date'            => $request->event_date,
            'event_time'            => $request->event_time,
            'location'              => $request->location,
            'ticket_type'           => 'FREE',   // always FREE
            'ticket_price'          => 0,        // always 0
            'total_seats'           => $request->total_seats,
            'registration_deadline' => $request->registration_deadline,
            'status'                => 'UPCOMING',
        ]);

        return ApiResponse::success(
            $this->formatEvent($event),
            'Event created successfully',
            201
        );
    }

    // ─────────────────────────────────
    // UPDATE EVENT (Super Admin)
    // ─────────────────────────────────
    public function updateEvent(Request $request)
    {
        $request->validate([
            'id'                    => 'required|exists:events,id',
            'title'                 => 'sometimes|string',
            'description'           => 'sometimes|string',
            'event_date'            => 'sometimes|date',
            'event_time'            => 'sometimes',
            'location'              => 'nullable|string',
            'total_seats'           => 'sometimes|integer|min:0',
            'registration_deadline' => 'nullable|date',
            'image'                 => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            // ticket_type, ticket_price, is_online, online_link removed
        ]);

        $event = Event::find($request->id);

        if ($request->hasFile('image')) {
            if ($event->image) Storage::disk('public')->delete($event->image);
            $image        = $request->file('image');
            $fileName     = 'event_' . time() . '_' . $image->getClientOriginalName();
            $event->image = $image->storeAs('event_images', $fileName, 'public');
        }

        if ($request->filled('title'))       $event->title       = $request->title;
        if ($request->filled('description')) $event->description = $request->description;
        if ($request->filled('event_date'))  $event->event_date  = $request->event_date;
        if ($request->filled('event_time'))  $event->event_time  = $request->event_time;
        if ($request->has('location'))       $event->location    = $request->location;
        if ($request->filled('total_seats')) $event->total_seats = $request->total_seats;
        if ($request->has('registration_deadline'))
            $event->registration_deadline = $request->registration_deadline;

        $event->save();

        return ApiResponse::success($this->formatEvent($event), 'Event updated successfully');
    }

    // ─────────────────────────────────
    // TOGGLE STATUS (Super Admin)
    // ─────────────────────────────────
    public function toggleStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:events,id',
            'status' => 'required|in:UPCOMING,ONGOING,COMPLETED,CANCELLED',
        ]);

        $event         = Event::find($request->id);
        $event->status = $request->status;
        $event->save();

        return ApiResponse::success([
            'id'     => (string) $event->id,
            'title'  => $event->title,
            'status' => $event->status,
        ], 'Event status updated to ' . $request->status);
    }

    // ─────────────────────────────────
    // DELETE EVENT (Super Admin)
    // ─────────────────────────────────
    public function deleteEvent(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:events,id'
        ]);

        $event = Event::find($request->id);

        if ($event->image) Storage::disk('public')->delete($event->image);

        $event->delete();

        return ApiResponse::success(null, 'Event deleted successfully');
    }

    // ─────────────────────────────────
    // GET REGISTRATIONS (Admin)
    // ─────────────────────────────────
    public function getRegistrations($id)
    {
        $event = Event::find($id);

        if (!$event) return ApiResponse::error('Event not found.', 404);

        $registrations = EventRegistration::with([
            'user', 'memberProfile.district', 'memberProfile.unit'
        ])->where('event_id', $id)->get();

        $data = $registrations->map(fn($r) => [
            'id'             => (string) $r->id,
            'ticket_number'  => $r->ticket_number,
            'name'           => $r->user->name,
            'email'          => $r->user->email,
            'phone'          => $r->user->phone,
            'membership_id'  => $r->memberProfile->membership_id ?? '—',
            'district'       => $r->memberProfile->district->name,
            'unit'           => $r->memberProfile->unit->name,
            'payment_status' => 'FREE',
            'payment_amount' => '0',
            'registered_at'  => $r->registered_at
                ? Carbon::parse($r->registered_at)->format('d M Y h:i A')
                : (string) $r->created_at,
        ]);

        return ApiResponse::success([
            'event' => [
                'id'               => (string) $event->id,
                'title'            => $event->title,
                'event_date'       => $event->event_date->format('d M Y'),
                'total_seats'      => (string) $event->total_seats,
                'registered_count' => (string) $event->registered_count,
            ],
            'registrations' => $data,
        ], 'Registrations fetched successfully');
    }

    // ─────────────────────────────────
    // REGISTER FOR EVENT (Member)
    // ─────────────────────────────────
    public function registerForEvent(Request $request)
{
    $request->validate([
        'event_id' => 'required|exists:events,id'
    ]);

    $user    = auth()->user();
        $event = $this->visibleEventsQuery()->where('id', $request->event_id)->first();

    if (!$event) {
        return ApiResponse::error('Event not found.', 404);
    }
    $profile = MemberProfile::where('user_id', $user->id)->first();

    if (!$profile) {
        return ApiResponse::error('Member profile not found.', 404);
    }

    // ── Allow APPROVED or ACTIVE members ──
    if (!in_array($profile->status, ['APPROVED', 'ACTIVE'])) {
        return ApiResponse::error(
            'Only approved members can register for events.', 400
        );
    }

    if (!in_array($event->status, ['UPCOMING', 'ONGOING'])) {
        return ApiResponse::error('This event is not open for registration.', 400);
    }

    if ($event->registration_deadline && Carbon::now()->gt($event->registration_deadline)) {
        return ApiResponse::error('Registration deadline has passed.', 400);
    }

    if (!$event->hasSeatsAvailable()) {
        return ApiResponse::error('No seats available for this event.', 400);
    }

    if (EventRegistration::where('event_id', $event->id)->where('user_id', $user->id)->exists()) {
        return ApiResponse::error('You are already registered for this event.', 400);
    }

    $ticketNumber = EventRegistration::generateTicketNumber($event->id);

    $registration = EventRegistration::create([
        'event_id'          => $event->id,
        'user_id'           => $user->id,
        'member_profile_id' => $profile->id,
        'ticket_number'     => $ticketNumber,
        'payment_status'    => 'FREE',
        'payment_amount'    => 0,
        'registered_at'     => Carbon::now(),
    ]);

    $event->increment('registered_count');

    return ApiResponse::success([
        'id'             => (string) $registration->id,
        'ticket_number'  => $registration->ticket_number,
        'event_title'    => $event->title,
        'event_date'     => $event->event_date->format('d M Y'),
        'event_time'     => $event->event_time,
        'location'       => $event->location,
        'ticket_type'    => 'FREE',
        'payment_status' => 'FREE',
        'payment_amount' => '0',
    ], 'Successfully registered! Your ticket: ' . $ticketNumber);
}

    // ─────────────────────────────────
    // CANCEL REGISTRATION (Member)
    // ─────────────────────────────────
    public function cancelRegistration(Request $request)
    {
        $request->validate([
            'registration_id' => 'required|exists:event_registrations,id'
        ]);

        $registration = EventRegistration::find($request->registration_id);

        if ($registration->user_id !== auth()->id()) {
            return ApiResponse::error('Unauthorized.', 403);
        }

        if ($registration->event->status !== 'UPCOMING') {
            return ApiResponse::error(
                'Cannot cancel registration for ongoing or completed events.',
                400
            );
        }

        $registration->event->decrement('registered_count');
        $registration->delete();

        return ApiResponse::success(null, 'Registration cancelled successfully');
    }

    // ─────────────────────────────────
    // FORMAT EVENT HELPER (Detail / Admin)
    // ─────────────────────────────────
    private function formatEvent($event, $admin = false)
    {
        $data = [
            'id'                    => (string) $event->id,
            'title'                 => $event->title,
            'description'           => $event->description,
            'image'                 => $event->image
                ? asset('storage/' . $event->image) : null,
            'event_date'            => $event->event_date->format('d M Y'),
            'event_time'            => $event->event_time,
            'location'              => $event->location,
            'ticket_type'           => 'FREE',
            'ticket_price'          => '0',
            'total_seats'           => (string) $event->total_seats,
            'registered_count'      => (string) $event->registered_count,
            'remaining_seats'       => $event->total_seats == 0
                ? 'Unlimited'
                : (string) ($event->total_seats - $event->registered_count),
            'registration_deadline' => $event->registration_deadline
                ? $event->registration_deadline->format('d M Y') : null,
            'status'                => $event->status,
            'created_at'            => (string) $event->created_at,
        ];

        if ($admin && $event->relationLoaded('createdBy')) {
            $data['created_by'] = $event->createdBy->name;
        }

        return $data;
    }
}