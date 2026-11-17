<?php

namespace App\Http\Controllers\UnitAdmin;

use App\Http\Controllers\Controller;
use App\Models\MemberProfile;
use App\Models\Payment;
use App\Models\News;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\UnitAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Mail\MemberStatusMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Rules\MinimumAge;

class UnitAdminController extends Controller
{
    // ─────────────────────────────────
    // HELPER
    // ─────────────────────────────────
    private function myUnitId()
    {
        return UnitAdmin::where('user_id', Auth::id())->value('unit_id');
    }

    // ─────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────
    public function dashboard()
    {
        $unitId = $this->myUnitId();
        $unit   = \App\Models\Unit::with('district.state')->find($unitId);
        $base   = \App\Models\MemberProfile::where('unit_id', $unitId);

        $unitUserIds = (clone $base)->pluck('user_id');

        return view('unit-admin.dashboard', [
            'unit'           => $unit,
            'totalMembers'   => (clone $base)->count(),
            'pendingMembers' => (clone $base)->where('status', 'PENDING')->count(),
            'activeMembers'  => (clone $base)->where('status', 'ACTIVE')->count(),
            'approvedMembers'=> (clone $base)->where('status', 'APPROVED')->count(),
            'rejectedMembers'=> (clone $base)->where('status', 'REJECTED')->count(),

            'totalRevenue'   => Payment::whereIn('user_id', $unitUserIds)
                                    ->where('status', 'SUCCESS')
                                    ->sum('amount'),

            'recentMembers'  => (clone $base)->with('user')->latest()->take(6)->get(),
            'upcomingEvents' => \App\Models\Event::where('status', 'UPCOMING')
                        ->where('created_by', Auth::id())
                        ->latest()
                        ->take(3)->get(),

            // ── Current-workflow program counts (replaces old Life/Annual plan split) ──
            'committeesCount'   => \App\Models\Committee::where('created_by', Auth::id())->count(),
            'activeCommittees'  => \App\Models\Committee::where('created_by', Auth::id())->where('is_active', true)->count(),
            'schemesCount'      => \App\Models\Scheme::where('created_by', Auth::id())->count(),
            'activeSchemes'     => \App\Models\Scheme::where('created_by', Auth::id())->where('is_active', true)->count(),
            'otherFeesCount'    => \App\Models\OtherFee::where('created_by', Auth::id())->count(),
            'activeOtherFees'   => \App\Models\OtherFee::where('created_by', Auth::id())->where('is_active', true)->count(),
            'fundraisingCount'  => \App\Models\FundraisingCampaign::where('created_by', Auth::id())->count(),
            'activeFundraising' => \App\Models\FundraisingCampaign::where('created_by', Auth::id())->where('status', 'ACTIVE')->count(),

            'monthlyData' => \App\Models\MemberProfile::where('unit_id', $unitId)
                                ->selectRaw('MONTH(created_at) as month, YEAR(created_at) as year, COUNT(*) as count')
                                ->where('created_at', '>=', now()->subMonths(6))
                                ->groupBy('year', 'month')->orderBy('year')->orderBy('month')->get(),
        ]);
    }

    // ─────────────────────────────────
    // MEMBERS
    // ─────────────────────────────────
    public function members(Request $request)
    {
        $unitId = $this->myUnitId();
        $query  = MemberProfile::with(['user', 'unit', 'district', 'state'])
                    ->where('unit_id', $unitId);

        if ($request->filled('status')) $query->where('status', $request->status);

        return view('unit-admin.members', [
            'members'          => $query->latest()->paginate(20),
            'status'           => $request->status,
            'membership_type'  => $request->membership_type,
            'myUnit'           => \App\Models\Unit::with('district.state')->find($unitId),
        ]);
    }

    public function storeMember(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:100',
            'email'        => [
                'nullable',
                'email:rfc,dns',
                'max:150',
                'regex:/^[^@\s]+@[^@\s]+\.[a-zA-Z]{2,}$/',
                'unique:users,email',
            ],
            'phone'        => 'required|digits:10|unique:users,phone',
            'dob'          => ['required', 'date', 'before:today', new MinimumAge(18)],
            'organization' => 'required|string',
            'address'      => 'required|string|max:500',
            'photo'        => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'email.email'      => 'Enter a valid email address.',
            'email.dns'        => 'This email domain does not appear to accept mail. Please check for typos.',
            'email.max'        => 'Email cannot exceed 150 characters.',
            'email.regex'      => 'Email must include a valid domain (e.g. name@example.com).',
            'email.unique'     => 'This email is already registered.',
            'address.required' => 'Address is required.',
            'address.max'      => 'Address cannot exceed 500 characters.',
            'photo.required'   => 'Member photo is required.',
            'photo.image'      => 'The file must be an image (jpg, jpeg or png).',
            'photo.mimes'      => 'Photo must be a jpg, jpeg or png file.',
            'photo.max'        => 'Photo cannot exceed 2MB.',
        ]);

        // Email is optional: always store NULL (never '') so the unique index is safe
        $email = trim((string) $request->email) !== '' ? trim($request->email) : null;

        $unitAdmin = UnitAdmin::with('unit.district')->where('user_id', Auth::id())->first();

        // ── Ask FUMMA whether this phone is an active FUMMA member ──
        // Done before the DB transaction so the HTTP call never holds it open.
        $isFummaMember = app(\App\Services\FummaMemberService::class)->isActive($request->phone) ? 1 : 0;

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photo     = $request->file('photo');
            $photoPath = $photo->storeAs('member_photos',
                'member_' . time() . '_' . $photo->getClientOriginalName(), 'public');
        }

        // ── Create user + profile + approve, all atomically ──
        try {
            $profile = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $unitAdmin, $photoPath, $email, $isFummaMember) {
                $user = \App\Models\User::create([
                    'name'      => $request->name,
                    'email'     => $email,               // null when left blank
                    'phone'     => $request->phone,
                    'password'  => Hash::make($request->phone),
                    'role'      => 'member',
                    'is_active' => true,
                ]);

                $profile = \App\Models\MemberProfile::create([
                    'user_id'         => $user->id,
                    'unit_id'         => $unitAdmin->unit_id,
                    'district_id'     => $unitAdmin->unit->district_id,
                    'state_id'        => State::fixed()?->id ?? $unitAdmin->unit->district->state_id,
                    'status'          => 'PENDING',
                    'dob'             => $request->dob,
                    'organization'    => $request->organization,
                    'address'         => $request->address,
                    'photo'           => $photoPath,
                    'is_fumma_member' => $isFummaMember,
                ]);

                // Admin-created members skip the PENDING queue:
                // generates the Membership ID, sets APPROVED + joined_date
                return app(\App\Services\MembershipService::class)->approve($profile);
            });
        } catch (\Exception $e) {
            Log::error('Member creation failed: ' . $e->getMessage());

            if ($photoPath) {
                Storage::disk('public')->delete($photoPath);
            }

            return back()
                ->with('error', 'Something went wrong while creating the member. No account was created — please try again.')
                ->withInput();
        }

        // ── Approval email (outside the transaction, so a mail failure can't roll anything back) ──
        $profile->loadMissing(['user', 'unit']);

        if (empty($profile->user->email)) {
            $emailNote = ' (No email given — share the login details manually.)';
        } else {
            try {
                Mail::to($profile->user->email)->send(new \App\Mail\MemberApprovedMail(
                    memberName:   $profile->user->name,
                    membershipId: $profile->membership_id,
                    unitName:     $profile->unit->name ?? 'ECA Unit',
                    phone:        $profile->user->phone,
                ));
                $emailNote = ' Login details emailed to ' . $profile->user->email . '.';
            } catch (\Throwable $e) {
                Log::error('Approval email failed for member #' . $profile->id . ': ' . $e->getMessage());
                $emailNote = ' (Could not send the email to ' . $profile->user->email .
                             ' — please share the login details manually.)';
            }
        }

        $fummaNote = $isFummaMember ? ' FUMMA member: Yes.' : ' FUMMA member: No.';

        return back()->with(
            'success',
            $profile->user->name . ' added and approved! Membership ID: ' .
            $profile->membership_id . '.' . $fummaNote . $emailNote
        );
    }

    // ── null-safe relations so the JSON response can't 500 ──
    public function memberDetail($id)
    {
        $unitId = $this->myUnitId();
        $member = MemberProfile::with(['user', 'unit', 'district', 'state'])
                    ->where('unit_id', $unitId)
                    ->findOrFail($id);

        return response()->json([
            'name'          => $member->user->name ?? '—',
            'email'         => $member->user->email ?? '—',
            'phone'         => $member->user->phone ?? '—',
            'dob'           => $member->dob,
            'organization'  => $member->organization,
            'address'       => $member->address,
            'photo'         => $member->photo
                                ? asset('storage/' . $member->photo)
                                : null,
            'unit_name'     => $member->unit->name ?? '—',
            'district_name' => $member->district->name ?? '—',
            'state_name'    => $member->state->name ?? '—',
            'membership_id' => $member->membership_id ?? '—',
            'status'        => $member->status,
            'joined_date'   => $member->joined_date ?? '—',
            'expiry_date'   => $member->expiry_date ?? '—',
            'registered_on' => $member->created_at->format('d M Y'),
        ]);
    }

    public function approveMember(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:member_profiles,id',
        ]);

        $unitId  = $this->myUnitId();
        $profile = MemberProfile::with(['user', 'unit'])
                        ->where('unit_id', $unitId)
                        ->findOrFail($request->id);

        if (!in_array($profile->status, ['PENDING', 'REJECTED'])) {
            return back()->with(
                'error',
                'Only PENDING or REJECTED members can be approved!'
            );
        }

        // ── Approve + generate Membership ID ──
        $profile = app(\App\Services\MembershipService::class)->approve($profile);

        if (empty($profile->user->email)) {
            $emailNote = ' (No email on file — share the login details manually.)';
        } else {
            try {
                Mail::to($profile->user->email)->send(new \App\Mail\MemberApprovedMail(
                    memberName:   $profile->user->name,
                    membershipId: $profile->membership_id,
                    unitName:     $profile->unit->name ?? 'ECA Unit',
                    phone:        $profile->user->phone,
                ));
                $emailNote = ' Email sent to ' . $profile->user->email;
            } catch (\Throwable $e) {
                Log::error('Approval email failed for member #' . $profile->id . ': ' . $e->getMessage());
                $emailNote = ' (Could not send email — the address on file, ' .
                             $profile->user->email . ', looks invalid or undeliverable.)';
            }
        }

        return back()->with(
            'success',
            $profile->user->name . ' approved! Membership ID: ' . $profile->membership_id . '.' . $emailNote
        );
    }

    public function rejectMember(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:member_profiles,id',
            'reason' => 'nullable|string',
        ]);

        $unitId  = $this->myUnitId();
        $profile = MemberProfile::with(['user', 'unit'])
                        ->where('unit_id', $unitId)
                        ->findOrFail($request->id);

        if (!in_array($profile->status, ['PENDING', 'APPROVED'])) {
            return back()->with(
                'error',
                'Only PENDING or APPROVED members can be rejected!'
            );
        }

        $profile->status = 'REJECTED';
        $profile->save();

        if (empty($profile->user->email)) {
            $emailNote = ' (No email on file — no notification sent.)';
        } else {
            try {
                $this->sendStatusMail($profile, 'rejected', $request->reason ?? null);
                $emailNote = ' Email sent to ' . $profile->user->email;
            } catch (\Exception $e) {
                Log::error('Status email (reject) failed for member #' . $profile->id . ': ' . $e->getMessage());
                $emailNote = ' (Could not send email — the address on file, ' .
                             $profile->user->email . ', looks invalid or undeliverable.)';
            }
        }

        return back()->with(
            'success',
            $profile->user->name . ' rejected.' . $emailNote
        );
    }
    
    public function deleteMember(Request $request)
{
    $request->validate([
        'id'       => 'required|integer',
        'password' => 'required|string',
    ], [
        'password.required' => 'Enter your password to confirm deletion.',
    ]);

    // Verify the logged-in unit admin's password
    $admin = Auth::guard('unit_admin')->user() ?? Auth::user();

    if (!$admin || !Hash::check($request->password, $admin->password)) {
        return back()->with('error', 'Incorrect password. Member was not deleted.');
    }

    // Only members inside this admin's own unit
    $profile = MemberProfile::where('unit_id', $this->myUnitId())->find($request->id);

    if (!$profile) {
        return back()->with('error', 'This member does not belong to your unit.');
    }

    try {
        app(\App\Services\MemberArchiveService::class)
            ->archiveAndDelete($profile, $admin->id, 'unit_admin');
    } catch (\Throwable $e) {
        Log::error('Member delete failed for profile #' . $profile->id . ': ' . $e->getMessage());
        return back()->with('error', 'Could not delete this member. They may still have linked records (payments, registrations, etc.).');
    }

    return back()->with('success', 'Member deleted successfully!');
}

    // ─────────────────────────────────
    // SEND STATUS EMAIL (reusable)
    // NOTE: this intentionally does NOT catch its own exceptions.
    // Callers (approveMember/rejectMember) decide how to handle failure,
    // so they can customize the message shown back to the admin.
    // ─────────────────────────────────
    private function sendStatusMail(
        \App\Models\MemberProfile $profile,
        string $status,
        ?string $reason = null
    ): void {
        $profile->loadMissing(['user', 'unit']);

        $unitAdminEmail = \App\Models\UnitAdmin::with('user')
            ->where('unit_id', $profile->unit_id)
            ->first()?->user->email
            ?? config('mail.from.address');

        Mail::to($profile->user->email)
            ->send(new MemberStatusMail(
                memberName:   $profile->user->name,
                unitName:     $profile->unit->name,
                status:       $status,
                reason:       $reason,
                contactEmail: $unitAdminEmail,
                loginUrl:     url('/member/login'),
            ));
    }

    // ─────────────────────────────────
    // NEWS
    // ─────────────────────────────────
    public function news()
    {
        return view('unit-admin.news', [
            'news' => News::with('createdBy')
                        ->where('created_by', Auth::id())
                        ->latest()
                        ->paginate(10),
        ]);
    }

    public function storeNews(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:200',
            'content' => 'required|string',
            'status'  => 'required|in:DRAFT,PUBLISHED',
            'image'   => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ], [
            'title.max'      => 'Title cannot exceed 200 characters.',
            'image.required' => 'A news image is required.',
        ]);

        // Image is required, so it is always present after validation
        $image     = $request->file('image');
        $imagePath = $image->storeAs(
            'news_images',
            'news_' . time() . '_' . $image->getClientOriginalName(),
            'public'
        );

        try {
            News::create([
                'created_by'   => Auth::id(),
                'title'        => $request->input('title'),
                'content'      => $request->input('content'),
                'image'        => $imagePath,
                'status'       => $request->input('status'),
                'published_at' => $request->input('status') === 'PUBLISHED' ? Carbon::now() : null,
            ]);
        } catch (\Exception $e) {
            Log::error('News creation failed: ' . $e->getMessage());

            Storage::disk('public')->delete($imagePath);

            return back()
                ->with('error', 'Something went wrong while creating the news. No news was created — please try again.')
                ->withInput();
        }

        return back()->with('success', 'News created successfully!');
    }

    public function updateNews(Request $request)
    {
        // Ownership check first: only the creator can edit
        $news = News::where('id', $request->id)
                    ->where('created_by', Auth::id())
                    ->firstOrFail();

        $request->validate([
            'id'      => 'required|exists:news,id',
            'title'   => 'required|string|max:200',
            'content' => 'required|string',
            // optional if the news already has an image, required if it doesn't
            'image'   => [$news->image ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ], [
            'title.max'      => 'Title cannot exceed 200 characters.',
            'image.required' => 'A news image is required.',
        ]);

        $oldImage = $news->image;
        $newImage = null;

        try {
            if ($request->hasFile('image')) {
                $image       = $request->file('image');
                $newImage    = $image->storeAs(
                    'news_images',
                    'news_' . time() . '_' . $image->getClientOriginalName(),
                    'public'
                );
                $news->image = $newImage;
            }

            $news->title   = $request->title;
            $news->content = $request->input('content');
            $news->save();

            // Delete the old file only after the save succeeded
            if ($newImage && $oldImage) {
                Storage::disk('public')->delete($oldImage);
            }
        } catch (\Exception $e) {
            Log::error('News update failed: ' . $e->getMessage());

            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            return back()
                ->with('error', 'Something went wrong while updating the news. Please try again.')
                ->withInput();
        }

        return back()->with('success', 'News updated successfully!');
    }

    public function toggleNewsStatus(Request $request)
    {
        $request->validate(['id' => 'required|exists:news,id']);

        $news = News::where('id', $request->id)
                    ->where('created_by', Auth::id())
                    ->firstOrFail();

        if ($news->status === 'DRAFT') {
            $news->status       = 'PUBLISHED';
            $news->published_at = Carbon::now();
        } else {
            $news->status       = 'DRAFT';
            $news->published_at = null;
        }
        $news->save();

        return back()->with('success', 'News ' . ($news->status === 'PUBLISHED' ? 'published' : 'unpublished') . '!');
    }

    public function deleteNews(Request $request)
    {
        $news = News::where('id', $request->id)
                    ->where('created_by', Auth::id())
                    ->firstOrFail();

        if ($news->image) Storage::disk('public')->delete($news->image);
        $news->delete();

        return back()->with('success', 'News deleted successfully!');
    }

    // ─────────────────────────────────
    // EVENTS
    // ─────────────────────────────────
    public function events()
    {
        // user_ids of members in MY unit
        $unitUserIds = MemberProfile::where('unit_id', $this->myUnitId())->select('user_id');

        return view('unit-admin.events', [
            'events' => Event::with('createdBy')
                ->withCount(['registrations' => fn($q) => $q->whereIn('user_id', $unitUserIds)])
                ->where('created_by', Auth::id())
                ->latest()
                ->paginate(10),
        ]);
    }

    public function storeEvent(Request $request)
    {
        $request->validate([
            'title'                 => 'required|string|max:200',
            'description'           => 'required|string',
            'event_date'            => 'required|date|after_or_equal:today',
            'event_time'            => 'required',
            'location'              => 'nullable|string',
            'total_seats'           => 'required|integer|min:1|max:1000',
            'registration_deadline' => 'nullable|date|before_or_equal:event_date',
            'image'                 => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ], [
            'title.max'                             => 'Event title cannot exceed 200 characters.',
            'event_date.after_or_equal'             => 'Event date cannot be in the past.',
            'total_seats.min'                       => 'Total seats must be at least 1.',
            'total_seats.max'                       => 'Total seats cannot exceed 1,000.',
            'registration_deadline.before_or_equal' => 'Registration deadline must be on or before the event date.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $fileName  = 'event_' . time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('event_images', $fileName, 'public');
        }

        Event::create([
            'created_by'            => Auth::id(),
            'title'                 => $request->title,
            'description'           => $request->description,
            'image'                 => $imagePath,
            'event_date'            => $request->event_date,
            'event_time'            => $request->event_time,
            'location'              => $request->location,
            'ticket_type'           => 'FREE',
            'ticket_price'          => 0,
            'total_seats'           => $request->total_seats,
            'registration_deadline' => $request->registration_deadline,
            'status'                => 'UPCOMING',
        ]);

        return back()->with('success', 'Event created successfully!');
    }

    public function updateEvent(Request $request)
    {
        $request->validate([
            'id'                    => 'required|exists:events,id',
            'title'                 => 'required|string|max:200',
            'description'           => 'required|string',
            'event_date'            => 'required|date',
            'event_time'            => 'required',
            'location'              => 'nullable|string',
            'total_seats'           => 'required|integer|min:1|max:1000',
            'registration_deadline' => 'nullable|date|before_or_equal:event_date',
            'image'                 => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ], [
            'title.max'                             => 'Event title cannot exceed 200 characters.',
            'total_seats.min'                       => 'Total seats must be at least 1.',
            'total_seats.max'                       => 'Total seats cannot exceed 1,000.',
            'registration_deadline.before_or_equal' => 'Registration deadline must be on or before the event date.',
        ]);

        $event = Event::where('id', $request->id)
                      ->where('created_by', Auth::id())
                      ->firstOrFail();

        if ($request->hasFile('image')) {
            if ($event->image) Storage::disk('public')->delete($event->image);
            $image        = $request->file('image');
            $fileName     = 'event_' . time() . '_' . $image->getClientOriginalName();
            $event->image = $image->storeAs('event_images', $fileName, 'public');
        }

        $event->title                 = $request->title;
        $event->description           = $request->description;
        $event->event_date            = $request->event_date;
        $event->event_time            = $request->event_time;
        $event->location              = $request->location;
        $event->total_seats           = $request->total_seats;
        $event->registration_deadline = $request->registration_deadline;

        $event->save();
        return back()->with('success', 'Event updated successfully!');
    }

    public function updateEventStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:events,id',
            'status' => 'required|in:UPCOMING,ONGOING,COMPLETED,CANCELLED',
        ]);

        $event = Event::where('id', $request->id)
                      ->where('created_by', Auth::id())
                      ->firstOrFail();

        $event->status = $request->status;
        $event->save();

        return back()->with('success', 'Event status updated to ' . $request->status . '!');
    }

    public function deleteEvent(Request $request)
    {
        $event = Event::where('id', $request->id)
                      ->where('created_by', Auth::id())
                      ->firstOrFail();

        if ($event->image) Storage::disk('public')->delete($event->image);
        $event->delete();

        return back()->with('success', 'Event deleted successfully!');
    }

    public function eventDetail($id)
    {
        $event = Event::where('id', $id)
                      ->where('created_by', Auth::id())
                      ->first();

        if (!$event) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'title'          => $event->title,
            'description'    => $event->description,
            'event_date_raw' => $event->event_date->format('Y-m-d'),
            'event_time'     => $event->event_time,
            'location'       => $event->location,
            'total_seats'    => $event->total_seats,
            'deadline_raw'   => $event->registration_deadline
                ? $event->registration_deadline->format('Y-m-d') : null,
        ]);
    }

    public function eventRegistrations($id)
    {
        $event = Event::where('id', $id)->where('created_by', Auth::id())->firstOrFail();

        $profiles = MemberProfile::with(['unit', 'district'])
            ->where('unit_id', $this->myUnitId())
            ->get()
            ->keyBy('user_id');

        $registrations = EventRegistration::with('user')
            ->where('event_id', $id)
            ->whereIn('user_id', $profiles->keys())
            ->get()
            ->each(fn($reg) => $reg->setRelation('memberProfile', $profiles->get($reg->user_id)));

        return view('unit-admin.event-registrations', compact('event', 'registrations'));
    }

    // ─────────────────────────────────
    // CHANGE PASSWORD
    // ─────────────────────────────────
    public function showChangePassword()
    {
        return view('unit-admin.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Current password is incorrect!');
        }

        if (Hash::check($request->new_password, $user->password)) {
            return back()->with('error', 'New password must be different from current password!');
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return back()->with('success', 'Password changed successfully!');
    }

    // ─────────────────────────────────
    // BULK IMPORT PAGE
    // ─────────────────────────────────
    public function bulkImport()
    {
        $unitId = $this->myUnitId();
        $unit   = \App\Models\Unit::with('district.state')->find($unitId);

        return view('unit-admin.bulk-import', [
            'myUnit' => $unit,
        ]);
    }

    // ─────────────────────────────────
    // DOWNLOAD CSV TEMPLATE
    // ─────────────────────────────────
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="member_import_template.csv"',
        ];

        $columns = [
            'name',
            'email',
            'phone',
            'dob',
            'organization',
            'address',
        ];

        // Row 1 has an email, row 2 leaves the email blank (optional).
        $rows = [
            ['Fathima Beevi', 'fathima@gmail.com', '9845001001', '1990-05-15', 'Kozhikode Public School', 'Main Road, Kozhikode'],
            ['Suresh Kumar',  '',                  '9845001002', '1985-08-20', 'Kozhikode College',        'Market Street, Kozhikode'],
        ];

        $callback = function () use ($columns, $rows) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($rows as $row) { fputcsv($file, $row); }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // ─────────────────────────────────
    // PROCESS BULK IMPORT (email optional)
    // ─────────────────────────────────
    public function processBulkImport(Request $request)
    {
        // Each valid row may call the FUMMA API (10s timeout max), so allow enough time.
        set_time_limit(300);

        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $unitAdmin  = \App\Models\UnitAdmin::with('unit.district.state')
                        ->where('user_id', Auth::id())->first();
        $unitId     = $unitAdmin->unit_id;
        $districtId = $unitAdmin->unit->district_id;
        $stateId    = State::fixed()?->id ?? $unitAdmin->unit->district->state_id;

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        fgetcsv($handle); // skip header

        $results = [
            'success' => [], 'errors' => [],
            'total' => 0, 'created' => 0, 'failed' => 0,
        ];

        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // skip completely empty rows
            if (count(array_filter($row, fn($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }

            $results['total']++;

            $data = [
                'name'         => trim($row[0] ?? ''),
                'email'        => trim($row[1] ?? ''),
                'phone'        => trim($row[2] ?? ''),
                'dob'          => trim($row[3] ?? ''),
                'organization' => trim($row[4] ?? ''),
                'address'      => trim($row[5] ?? ''),
            ];

            $errors = [];
            if (empty($data['name'])) $errors[] = 'Name is required';

            // ── Email (OPTIONAL: only validated when provided) ──
            $email = $data['email'] !== '' ? $data['email'] : null;

            if ($email !== null) {
                $emailValid = true;

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = 'Invalid email';
                    $emailValid = false;
                }
                if (!preg_match('/\.[a-zA-Z]{2,}$/', $email)) {
                    $errors[] = 'Email must include a valid domain';
                    $emailValid = false;
                }
                if (strlen($email) > 150) {
                    $errors[] = 'Email cannot exceed 150 characters';
                    $emailValid = false;
                }
                if ($emailValid && !$this->emailDomainHasMailRecords($email)) {
                    $errors[] = 'Email domain does not appear to accept mail (check for typos)';
                }
                if (\App\Models\User::where('email', $email)->exists()) {
                    $errors[] = 'Email already exists';
                }
            }

            // ── Phone ──
            if (empty($data['phone']) || strlen($data['phone']) != 10) $errors[] = 'Phone must be 10 digits';
            if (\App\Models\User::where('phone', $data['phone'])->exists()) $errors[] = 'Phone already exists';

            // ── DOB ──
            $dob = null;
            if (empty($data['dob'])) {
                $errors[] = 'DOB is required';
            } else {
                try {
                    $parsedDob = Carbon::parse($data['dob']);
                    if ($parsedDob->isFuture()) {
                        $errors[] = 'DOB cannot be in the future';
                    } elseif ($parsedDob->age < 18) {
                        $errors[] = 'Member must be at least 18 years old';
                    } else {
                        $dob = $parsedDob->format('Y-m-d');
                    }
                } catch (\Exception $e) {
                    $errors[] = 'DOB is not a valid date (use YYYY-MM-DD)';
                }
            }

            // ── Organization / address ──
            if (empty($data['organization']))   $errors[] = 'Organization is required';
            if (empty($data['address']))        $errors[] = 'Address is required';
            if (strlen($data['address']) > 500) $errors[] = 'Address cannot exceed 500 characters';

            if (!empty($errors)) {
                $results['errors'][] = ['row' => $rowNumber, 'name' => $data['name'], 'email' => $email, 'errors' => $errors];
                $results['failed']++;
                continue;
            }

            // ── Row is valid: ask FUMMA whether this phone is an active FUMMA member ──
            $isFummaMember = app(\App\Services\FummaMemberService::class)->isActive($data['phone']) ? 1 : 0;

            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($data, $email, $dob, $unitId, $districtId, $stateId, $isFummaMember) {
                    $user = \App\Models\User::create([
                        'name'      => $data['name'],
                        'email'     => $email,   // null when left blank
                        'phone'     => $data['phone'],
                        'password'  => Hash::make($data['phone']),
                        'role'      => 'member',
                        'is_active' => true,
                    ]);

                    \App\Models\MemberProfile::create([
                        'user_id'         => $user->id,
                        'unit_id'         => $unitId,
                        'district_id'     => $districtId,
                        'state_id'        => $stateId,
                        'status'          => 'PENDING',
                        'dob'             => $dob,
                        'organization'    => $data['organization'],
                        'address'         => $data['address'],
                        'photo'           => null,
                        'is_fumma_member' => $isFummaMember,
                    ]);
                });

                $results['success'][] = [
                    'row'             => $rowNumber,
                    'name'            => $data['name'],
                    'email'           => $email,
                    'is_fumma_member' => $isFummaMember,
                ];
                $results['created']++;

            } catch (\Exception $e) {
                Log::error('Bulk import row ' . $rowNumber . ' failed: ' . $e->getMessage());

                $results['errors'][] = ['row' => $rowNumber, 'name' => $data['name'], 'email' => $email, 'errors' => ['Something went wrong while saving this row']];
                $results['failed']++;
            }
        }

        fclose($handle);
        return back()->with('import_results', $results);
    }

    // ─────────────────────────────────
    // Helper: does this email's domain actually have mail (MX/A) records?
    // Used by bulk import since Laravel's 'email:rfc,dns' validation rule
    // only applies to normal form requests, not manual per-row CSV checks.
    // ─────────────────────────────────
    private function emailDomainHasMailRecords(string $email): bool
    {
        $domain = substr(strrchr($email, '@'), 1);
        if (!$domain) return false;

        return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
    }

    public function payments(Request $request)
    {
        $unitId = $this->myUnitId();

        $unitUserIds = \App\Models\MemberProfile::where('unit_id', $unitId)
                        ->pluck('user_id');

        $query = Payment::with(['user', 'memberProfile.unit', 'memberProfile.district'])
                    ->whereIn('user_id', $unitUserIds)
                    ->latest();

        if ($request->filled('status'))       $query->where('status', $request->status);
        if ($request->filled('payment_type')) $query->where('payment_type', $request->payment_type);
        if ($request->filled('date_from'))    $query->whereDate('created_at', '>=', $request->date_from);
        if ($request->filled('date_to'))      $query->whereDate('created_at', '<=', $request->date_to);

        $payments = $query->get();

        $unit = \App\Models\Unit::with('district.state')->find($unitId);

        return view('unit-admin.payments', [
            'payments'       => $payments,
            'unit'           => $unit,
            'totalRevenue'   => Payment::whereIn('user_id', $unitUserIds)->where('status','SUCCESS')->sum('amount'),
            'memberRevenue'  => Payment::whereIn('user_id', $unitUserIds)->where('status','SUCCESS')->where('payment_type','MEMBERSHIP')->sum('amount'),
            'eventRevenue'   => Payment::whereIn('user_id', $unitUserIds)->where('status','SUCCESS')->where('payment_type','EVENT')->sum('amount'),
            'renewalRevenue' => Payment::whereIn('user_id', $unitUserIds)->where('status','SUCCESS')->where('payment_type','RENEWAL')->sum('amount'),
            'totalCount'     => Payment::whereIn('user_id', $unitUserIds)->where('status','SUCCESS')->count(),
            'pendingCount'   => Payment::whereIn('user_id', $unitUserIds)->where('status','PENDING')->count(),
            'failedCount'    => Payment::whereIn('user_id', $unitUserIds)->where('status','FAILED')->count(),
        ]);
    }

    // ─────────────────────────────────
    // FUNDRAISING CAMPAIGNS
    // ─────────────────────────────────
    public function fundraising()
    {
        return view('unit-admin.fundraising', [
            'campaigns' => \App\Models\FundraisingCampaign::where('created_by', Auth::id())
                ->latest()
                ->paginate(20),
        ]);
    }

    public function storeFundraisingCampaign(Request $request)
    {
        $request->validate([
            'title'         => 'required|string|max:200',
            'description'   => 'required|string',
            'target_amount' => ['required', 'numeric', 'min:1', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'raised_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'end_date'      => 'nullable|date|after_or_equal:today',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ], [
            'title.max'           => 'Title cannot exceed 200 characters.',
            'target_amount.max'   => 'Target amount cannot exceed 7 digits (max ₹99,99,999).',
            'target_amount.regex' => 'Enter a valid amount with up to 7 digits.',
            'raised_amount.max'   => 'Raised amount cannot exceed 7 digits (max ₹99,99,999).',
            'raised_amount.regex' => 'Enter a valid amount with up to 7 digits.',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $fileName  = 'campaign_' . time() . '_' . $image->getClientOriginalName();
            $imagePath = $image->storeAs('campaign_images', $fileName, 'public');
        }

        \App\Models\FundraisingCampaign::create([
            'created_by'    => Auth::id(),
            'title'         => $request->title,
            'description'   => $request->description,
            'image'         => $imagePath,
            'target_amount' => $request->target_amount,
            'raised_amount' => $request->raised_amount ?? 0,
            'end_date'      => $request->end_date,
            'status'        => 'ACTIVE',
        ]);

        return back()->with('success', 'Campaign created successfully!');
    }

    public function updateFundraisingCampaign(Request $request)
    {
        $request->validate([
            'id'            => 'required|exists:fundraising_campaigns,id',
            'title'         => 'required|string|max:200',
            'description'   => 'required|string',
            'target_amount' => ['required', 'numeric', 'min:1', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'raised_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'end_date'      => 'nullable|date',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ], [
            'title.max'           => 'Title cannot exceed 200 characters.',
            'target_amount.max'   => 'Target amount cannot exceed 7 digits (max ₹99,99,999).',
            'target_amount.regex' => 'Enter a valid amount with up to 7 digits.',
            'raised_amount.max'   => 'Raised amount cannot exceed 7 digits (max ₹99,99,999).',
            'raised_amount.regex' => 'Enter a valid amount with up to 7 digits.',
        ]);

        $campaign = \App\Models\FundraisingCampaign::where('created_by', Auth::id())
            ->findOrFail($request->id);

        if ($request->hasFile('image')) {
            if ($campaign->image) {
                Storage::disk('public')->delete($campaign->image);
            }
            $image           = $request->file('image');
            $fileName        = 'campaign_' . time() . '_' . $image->getClientOriginalName();
            $campaign->image = $image->storeAs('campaign_images', $fileName, 'public');
        }

        $campaign->title         = $request->title;
        $campaign->description   = $request->description;
        $campaign->target_amount = $request->target_amount;
        $campaign->raised_amount = $request->raised_amount ?? $campaign->raised_amount;
        $campaign->end_date      = $request->end_date;
        $campaign->save();

        return back()->with('success', 'Campaign updated successfully!');
    }

    public function updateFundraisingStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|exists:fundraising_campaigns,id',
            'status' => 'required|in:ACTIVE,COMPLETED,CANCELLED',
        ]);

        $campaign = \App\Models\FundraisingCampaign::where('created_by', Auth::id())
            ->findOrFail($request->id);

        $campaign->status = $request->status;
        $campaign->save();

        return back()->with('success', 'Campaign status updated to ' . $request->status . '!');
    }

    public function deleteFundraisingCampaign(Request $request)
    {
        $campaign = \App\Models\FundraisingCampaign::where('created_by', Auth::id())
            ->findOrFail($request->id);

        if ($campaign->image) {
            Storage::disk('public')->delete($campaign->image);
        }

        $campaign->delete();
        return back()->with('success', 'Campaign deleted successfully!');
    }

    public function fundraisingCampaignDetail($id)
    {
        $campaign = \App\Models\FundraisingCampaign::where('created_by', Auth::id())
            ->find($id);

        if (!$campaign) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'title'          => $campaign->title,
            'description'    => $campaign->description,
            'target_amount'  => $campaign->target_amount,
            'raised_amount'  => $campaign->raised_amount,
            'end_date_raw'   => $campaign->end_date ? $campaign->end_date->format('Y-m-d') : null,
            'status'         => $campaign->status,
        ]);
    }

    // ─────────────────────────────────
    // COMMITTEES
    // ─────────────────────────────────
    public function committees()
    {
        return view('unit-admin.committees', [
            'committees' => \App\Models\Committee::withCount('members')
                ->where('created_by', Auth::id())
                ->latest()
                ->paginate(20),
        ]);
    }

    public function storeCommittee(Request $request)
    {
        $request->validate([
            'name'        => 'required|string',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ]);

        \App\Models\Committee::create([
            'name'        => $request->name,
            'description' => $request->description,
            'start_date'  => $request->start_date,
            'end_date'    => $request->end_date,
            'created_by'  => Auth::id(),
            'is_active'   => true,
        ]);

        return back()->with('success', 'Committee created successfully! Now add members to it.');
    }

    public function updateCommittee(Request $request)
    {
        $request->validate([
            'id'          => 'required|exists:committees,id',
            'name'        => 'required|string',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ]);

        \App\Models\Committee::where('created_by', Auth::id())
            ->findOrFail($request->id)
            ->update([
                'name'        => $request->name,
                'description' => $request->description,
                'start_date'  => $request->start_date,
                'end_date'    => $request->end_date,
            ]);

        return back()->with('success', 'Committee updated successfully!');
    }

    public function toggleCommitteeStatus(Request $request)
    {
        $request->validate(['id' => 'required|exists:committees,id']);

        $committee = \App\Models\Committee::where('created_by', Auth::id())
            ->findOrFail($request->id);

        $committee->is_active = !$committee->is_active;
        $committee->save();

        $status = $committee->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Committee {$status}!");
    }

    public function deleteCommittee(Request $request)
    {
        $committee = \App\Models\Committee::where('created_by', Auth::id())
            ->findOrFail($request->id);

        $committee->delete();
        return back()->with('success', 'Committee deleted successfully!');
    }

    public function committeeDetail($id)
    {
        $committee = \App\Models\Committee::where('created_by', Auth::id())->find($id);

        if (!$committee) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'name'           => $committee->name,
            'description'    => $committee->description,
            'start_date_raw' => $committee->start_date->format('Y-m-d'),
            'end_date_raw'   => $committee->end_date?->format('Y-m-d'),
            'is_active'      => $committee->is_active,
        ]);
    }

    public function committeeMembers($id)
    {
        $unitId    = $this->myUnitId();
        $committee = \App\Models\Committee::with(['members.user'])
            ->where('created_by', Auth::id())
            ->find($id);

        if (!$committee) {
            return redirect()->route('unit-admin.committees')->with('error', 'Committee not found!');
        }

        $assignedIds = $committee->members->pluck('id');

        $availableMembers = \App\Models\MemberProfile::with('user')
            ->where('unit_id', $unitId)
            ->where('status', 'APPROVED')
            ->whereNotIn('id', $assignedIds)
            ->get();

        return view('unit-admin.committee-members', [
            'committee'        => $committee,
            'availableMembers' => $availableMembers,
        ]);
    }

    public function addCommitteeMember(Request $request)
    {
        $request->validate([
            'committee_id'      => 'required|exists:committees,id',
            'member_profile_id' => 'required|exists:member_profiles,id',
            'role'              => 'required|string|max:100',
        ]);

        $unitId    = $this->myUnitId();
        $committee = \App\Models\Committee::where('created_by', Auth::id())
            ->findOrFail($request->committee_id);

        $member = \App\Models\MemberProfile::where('unit_id', $unitId)
            ->findOrFail($request->member_profile_id);

        if ($committee->members()->where('member_profiles.id', $member->id)->exists()) {
            return back()->with('error', 'This member is already part of the committee.');
        }

        $committee->members()->attach($member->id, ['role' => $request->role]);

        return back()->with('success', 'Member added to committee!');
    }

    public function updateCommitteeMemberRole(Request $request)
    {
        $request->validate([
            'committee_id'      => 'required|exists:committees,id',
            'member_profile_id' => 'required|exists:member_profiles,id',
            'role'              => 'required|string|max:100',
        ]);

        $committee = \App\Models\Committee::where('created_by', Auth::id())
            ->findOrFail($request->committee_id);

        $committee->members()->updateExistingPivot($request->member_profile_id, ['role' => $request->role]);

        return back()->with('success', 'Member role updated!');
    }

    public function removeCommitteeMember(Request $request)
    {
        $request->validate([
            'committee_id'      => 'required|exists:committees,id',
            'member_profile_id' => 'required|exists:member_profiles,id',
        ]);

        $committee = \App\Models\Committee::where('created_by', Auth::id())
            ->findOrFail($request->committee_id);

        $committee->members()->detach($request->member_profile_id);

        return back()->with('success', 'Member removed from committee.');
    }

    // ─────────────────────────────────
    // GALLERY
    // ─────────────────────────────────
    public function gallery()
    {
        return view('unit-admin.gallery', [
            'images' => \App\Models\GalleryImage::where('created_by', Auth::id())
                ->latest()
                ->paginate(24),
        ]);
    }

    public function storeGalleryImages(Request $request)
    {
        $request->validate([
            'images'   => 'required|array|min:1',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        foreach ($request->file('images') as $file) {
            $fileName = 'gallery_' . time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $path     = $file->storeAs('gallery_images', $fileName, 'public');

            \App\Models\GalleryImage::create([
                'image'      => $path,
                'created_by' => Auth::id(),
                'is_active'  => true,
            ]);
        }

        return back()->with('success', count($request->file('images')) . ' image(s) uploaded successfully!');
    }

    public function toggleGalleryImageStatus(Request $request)
    {
        $request->validate(['id' => 'required|exists:gallery_images,id']);

        $image = \App\Models\GalleryImage::where('created_by', Auth::id())
            ->findOrFail($request->id);

        $image->is_active = !$image->is_active;
        $image->save();

        $status = $image->is_active ? 'shown in' : 'hidden from';
        return back()->with('success', "Image {$status} the app gallery.");
    }

    public function deleteGalleryImage(Request $request)
    {
        $image = \App\Models\GalleryImage::where('created_by', Auth::id())
            ->findOrFail($request->id);

        if ($image->image) {
            Storage::disk('public')->delete($image->image);
        }

        $image->delete();
        return back()->with('success', 'Image deleted successfully!');
    }

    // ─────────────────────────────────
    // OTHER FEES (unit admin)
    // ─────────────────────────────────
    public function otherFees()
    {
        $unitId = $this->myUnitId();

        return view('unit-admin.other-fees', [
            'fees' => \App\Models\OtherFee::where('created_by', Auth::id())
                ->withCount(['payments as payments_count' => function ($q) use ($unitId) {
                    $q->whereHas('memberProfile', function ($q2) use ($unitId) {
                        $q2->where('unit_id', $unitId);
                    });
                }])
                ->latest()
                ->paginate(20),
        ]);
    }

    public function storeOtherFee(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:200',
            'amount'   => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'due_date' => 'nullable|date|after_or_equal:today',
        ], [
            'title.max'        => 'Title cannot exceed 200 characters.',
            'amount.max'       => 'Amount cannot exceed 7 digits (max ₹99,99,999).',
            'amount.regex'     => 'Enter a valid amount with up to 7 digits.',
            'due_date.date'    => 'Please enter a valid date.',
            'due_date.after_or_equal' => 'Due date cannot be in the past.',
        ]);

        \App\Models\OtherFee::create([
            'title'      => $request->title,
            'amount'     => $request->amount,
            'due_date'   => $request->due_date,
            'created_by' => Auth::id(),
            'is_active'  => true,
        ]);

        return back()->with('success', 'Other fee created successfully! It will apply to your unit\'s active members.');
    }

    public function updateOtherFee(Request $request)
    {
        $request->validate([
            'id'       => 'required|exists:other_fees,id',
            'title'    => 'required|string|max:200',
            'amount'   => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'due_date' => 'nullable|date|after_or_equal:today',
        ], [
            'title.max'        => 'Title cannot exceed 200 characters.',
            'amount.max'       => 'Amount cannot exceed 7 digits (max ₹99,99,999).',
            'amount.regex'     => 'Enter a valid amount with up to 7 digits.',
            'due_date.date'    => 'Please enter a valid date.',
            'due_date.after_or_equal' => 'Due date cannot be in the past.',
        ]);

        \App\Models\OtherFee::where('created_by', Auth::id())
            ->findOrFail($request->id)
            ->update([
                'title'    => $request->title,
                'amount'   => $request->amount,
                'due_date' => $request->due_date,
            ]);

        return back()->with('success', 'Other fee updated successfully!');
    }

    public function toggleOtherFeeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:other_fees,id'
        ]);

        $fee = \App\Models\OtherFee::where('created_by', Auth::id())
            ->findOrFail($request->id);

        $fee->is_active = !$fee->is_active;
        $fee->save();

        $status = $fee->is_active ? 'activated' : 'deactivated';
        return back()->with('success', 'Fee ' . $status . '! ' . ($fee->is_active
            ? 'It will now apply to members who check their dues.'
            : 'No new members will be charged this fee.'));
    }

    public function deleteOtherFee(Request $request)
    {
        $fee = \App\Models\OtherFee::where('created_by', Auth::id())
            ->findOrFail($request->id);

        if ($fee->payments()->where('status', 'PAID')->exists()) {
            return back()->with('error', 'Cannot delete! Some members have already paid this fee. Deactivate it instead.');
        }

        $fee->delete();
        return back()->with('success', 'Other fee deleted successfully!');
    }

    public function otherFeeDetail($id)
    {
        $fee = \App\Models\OtherFee::where('created_by', Auth::id())->find($id);

        if (!$fee) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'title'        => $fee->title,
            'amount'       => $fee->amount,
            'due_date_raw' => $fee->due_date ? $fee->due_date->format('Y-m-d') : null,
            'is_active'    => $fee->is_active,
        ]);
    }

    public function otherFeePayers($id)
    {
        $unitId = $this->myUnitId();

        $fee = \App\Models\OtherFee::where('created_by', Auth::id())->find($id);

        if (!$fee) {
            return redirect()->route('unit-admin.other-fees')->with('error', 'Fee not found!');
        }

        $payments = $fee->payments()
            ->whereHas('memberProfile', function ($q) use ($unitId) {
                $q->where('unit_id', $unitId);
            })
            ->with(['memberProfile.user', 'memberProfile.unit'])
            ->get();

        return view('unit-admin.other-fee-payers', [
            'fee'      => $fee,
            'payments' => $payments,
        ]);
    }

    // ─────────────────────────────────
    // SCHEMES
    // ─────────────────────────────────
    public function schemes()
    {
        $unitId = $this->myUnitId();

        return view('unit-admin.schemes', [
            'schemes' => \App\Models\Scheme::where('created_by', Auth::id())
                ->withCount([
                    'dues as dues_count' => function ($q) use ($unitId) {
                        $q->whereHas('memberProfile', function ($q2) use ($unitId) {
                            $q2->where('unit_id', $unitId);
                        });
                    },
                    'members as members_count' => function ($q) use ($unitId) {
                        $q->where('unit_id', $unitId);
                    },
                ])
                ->latest()
                ->paginate(20),
            'allMembers' => \App\Models\MemberProfile::with('user')
                ->where('unit_id', $unitId)
                ->where('status', 'APPROVED')
                ->get(),
        ]);
    }

    public function storeScheme(Request $request)
    {
        $unitId = $this->myUnitId();

        $request->validate([
            'title'           => 'required|string',
            'description'     => 'nullable|string',
            'amount'          => 'required|numeric|min:0.01',
            'duration_months' => 'required|integer|min:1',
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'member_ids'      => 'required|array|min:1',
            'member_ids.*'    => 'exists:member_profiles,id',
        ], [
            'member_ids.required' => 'Select at least one member for this scheme.',
        ]);

        $validMemberIds = \App\Models\MemberProfile::where('unit_id', $unitId)
            ->whereIn('id', $request->member_ids)
            ->pluck('id');

        $scheme = \App\Models\Scheme::create([
            'title'           => $request->title,
            'description'     => $request->description,
            'amount'          => $request->amount,
            'duration_months' => $request->duration_months,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'created_by'      => Auth::id(),
            'is_active'       => true,
        ]);

        $scheme->members()->sync($validMemberIds);

        return back()->with('success', 'Scheme created successfully! Applied to ' . $validMemberIds->count() . ' selected member(s).');
    }

    public function updateScheme(Request $request)
    {
        $unitId = $this->myUnitId();

        $request->validate([
            'id'              => 'required|exists:schemes,id',
            'title'           => 'required|string',
            'description'     => 'nullable|string',
            'amount'          => 'required|numeric|min:0.01',
            'duration_months' => 'required|integer|min:1',
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'member_ids'      => 'required|array|min:1',
            'member_ids.*'    => 'exists:member_profiles,id',
        ], [
            'member_ids.required' => 'Select at least one member for this scheme.',
        ]);

        $scheme = \App\Models\Scheme::where('created_by', Auth::id())
            ->findOrFail($request->id);

        $scheme->update([
            'title'           => $request->title,
            'description'     => $request->description,
            'amount'          => $request->amount,
            'duration_months' => $request->duration_months,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
        ]);

        $validMemberIds = \App\Models\MemberProfile::where('unit_id', $unitId)
            ->whereIn('id', $request->member_ids)
            ->pluck('id');

        $scheme->members()->sync($validMemberIds);

        return back()->with('success', 'Scheme updated successfully! Now applies to ' . $validMemberIds->count() . ' selected member(s).');
    }

    public function schemeDetail($id)
    {
        $unitId = $this->myUnitId();

        $scheme = \App\Models\Scheme::with(['members' => function ($q) use ($unitId) {
                $q->where('unit_id', $unitId)->with('user');
            }])
            ->where('created_by', Auth::id())
            ->find($id);

        if (!$scheme) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'title'           => $scheme->title,
            'description'     => $scheme->description,
            'amount'          => $scheme->amount,
            'duration_months' => $scheme->duration_months,
            'start_date_raw'  => $scheme->start_date?->format('Y-m-d'),
            'end_date_raw'    => $scheme->end_date?->format('Y-m-d'),
            'is_active'       => $scheme->is_active,
            'member_ids'      => $scheme->members->pluck('id'),
            'members'         => $scheme->members->map(function ($m) {
                return [
                    'id'    => $m->id,
                    'name'  => $m->user->name ?? '—',
                    'email' => $m->user->email ?? '—',
                ];
            }),
        ]);
    }

    public function toggleSchemeStatus(Request $request)
    {
        $request->validate(['id' => 'required|exists:schemes,id']);

        $scheme = \App\Models\Scheme::where('created_by', Auth::id())
            ->findOrFail($request->id);

        $scheme->is_active = !$scheme->is_active;
        $scheme->save();

        $status = $scheme->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Scheme {$status}!");
    }

    public function deleteScheme(Request $request)
    {
        $request->validate(['id' => 'required|exists:schemes,id']);

        $scheme = \App\Models\Scheme::where('created_by', Auth::id())
            ->findOrFail($request->id);

        if ($scheme->dues()->where('status', 'PAID')->exists()) {
            return back()->with('error', 'Cannot delete! Some members have already paid dues under this scheme. Deactivate it instead.');
        }

        $scheme->delete();
        return back()->with('success', 'Scheme deleted successfully!');
    }

    public function schemePayers($id)
    {
        $unitId = $this->myUnitId();

        $scheme = \App\Models\Scheme::where('created_by', Auth::id())->find($id);

        if (!$scheme) {
            return redirect()->route('unit-admin.schemes')->with('error', 'Scheme not found!');
        }

        $dues = $scheme->dues()
            ->whereHas('memberProfile', function ($q) use ($unitId) {
                $q->where('unit_id', $unitId);
            })
            ->with(['memberProfile.user', 'memberProfile.unit'])
            ->get();

        return view('unit-admin.scheme-payers', [
            'scheme' => $scheme,
            'dues'   => $dues,
        ]);
    }

    // ─────────────────────────────────
    // EXPENSES (unit admin)
    // ─────────────────────────────────
    public function expenses()
    {
        $today = Carbon::today();

        $expenses = \App\Models\Expense::where('created_by', Auth::id())
            ->latest('expense_date')
            ->paginate(20);

        $todaysExpense = \App\Models\Expense::where('created_by', Auth::id())
            ->whereDate('expense_date', $today)->sum('amount');

        $thisMonthExpense = \App\Models\Expense::where('created_by', Auth::id())
            ->whereYear('expense_date', $today->year)
            ->whereMonth('expense_date', $today->month)->sum('amount');

        $thisYearExpense = \App\Models\Expense::where('created_by', Auth::id())
            ->whereYear('expense_date', $today->year)->sum('amount');

        $totalTransactions = \App\Models\Expense::where('created_by', Auth::id())
            ->whereYear('expense_date', $today->year)->count();

        $categoryTotals = \App\Models\Expense::where('created_by', Auth::id())
            ->whereYear('expense_date', $today->year)
            ->whereMonth('expense_date', $today->month)
            ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        $monthlyTotals = \App\Models\Expense::where('created_by', Auth::id())
            ->whereYear('expense_date', $today->year)
            ->selectRaw('MONTH(expense_date) as month, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('month')
            ->orderByDesc('month')
            ->get();

        return view('unit-admin.expenses', [
            'expenses'   => $expenses,
            'categories' => ['Maintenance', 'Events', 'Utilities', 'Cleaning', 'Stationery', 'Others'],

            'todaysExpense'     => $todaysExpense,
            'thisMonthExpense'  => $thisMonthExpense,
            'thisYearExpense'   => $thisYearExpense,
            'totalTransactions' => $totalTransactions,
            'categoryTotals'    => $categoryTotals,
            'monthlyTotals'     => $monthlyTotals,
        ]);
    }

    public function storeExpense(Request $request)
    {
        $request->validate([
            'title'        => 'required|string|max:200',
            'category'     => 'required|string|max:100',
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'expense_date' => 'required|date',
            'expense_time' => 'nullable|date_format:H:i',
            'paid_to'      => 'nullable|string|max:150',
            'status'       => 'required|in:PENDING,APPROVED,PAID,REJECTED',
        ], [
            'amount.regex' => 'Enter a valid amount with up to 7 digits.',
        ]);

        \App\Models\Expense::create([
            'title'        => $request->title,
            'category'     => $request->category,
            'amount'       => $request->amount,
            'expense_date' => $request->expense_date,
            'expense_time' => $request->expense_time ?: now()->format('H:i'),
            'paid_to'      => $request->paid_to,
            'status'       => $request->status,
            'created_by'   => Auth::id(),
            'unit_id'      => $this->myUnitId(),   // ← local to this unit only
        ]);

        return back()->with('success', 'Expense recorded successfully!');
    }

    public function updateExpense(Request $request)
    {
        $request->validate([
            'id'           => 'required|exists:expenses,id',
            'title'        => 'required|string|max:200',
            'category'     => 'required|string|max:100',
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'expense_date' => 'required|date',
            'expense_time' => 'nullable|date_format:H:i',
            'paid_to'      => 'nullable|string|max:150',
            'status'       => 'required|in:PENDING,APPROVED,PAID,REJECTED',
        ], [
            'amount.regex' => 'Enter a valid amount with up to 7 digits.',
        ]);

        // ── Scoped to this unit admin's own expenses — prevents editing another unit's record by guessing the id ──
        \App\Models\Expense::where('created_by', Auth::id())
            ->findOrFail($request->id)
            ->update($request->only([
                'title', 'category', 'amount', 'expense_date', 'expense_time', 'paid_to', 'status',
            ]));

        return back()->with('success', 'Expense updated successfully!');
    }

    public function deleteExpense(Request $request)
    {
        $request->validate(['id' => 'required|exists:expenses,id']);

        \App\Models\Expense::where('created_by', Auth::id())
            ->findOrFail($request->id)
            ->delete();

        return back()->with('success', 'Expense deleted successfully!');
    }

    public function expenseDetail($id)
    {
        $expense = \App\Models\Expense::where('created_by', Auth::id())->find($id);
        if (!$expense) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'title'        => $expense->title,
            'category'     => $expense->category,
            'amount'       => $expense->amount,
            'expense_date' => $expense->expense_date->format('Y-m-d'),
            'expense_time' => $expense->expense_time,
            'paid_to'      => $expense->paid_to,
            'status'       => $expense->status,
        ]);
    }

    // ─────────────────────────────────
    // LOGOUT
    // ─────────────────────────────────
    public function logout(Request $request)
    {
        Auth::guard('unit_admin')->logout();
        return redirect()->route('unit-admin.login');
    }
}