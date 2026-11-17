<?php

namespace App\Http\Controllers\DistrictAdmin;

use App\Http\Controllers\Controller;
use App\Models\DistrictAdmin;
use App\Models\MemberProfile;
use App\Models\News;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\FundraisingCampaign;
use App\Models\Committee;
use App\Models\Scheme;
use App\Models\OtherFee;
use App\Models\GalleryImage;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * District Admin — content & programs.
 * Everything here is owned by the logged-in district admin (created_by = Auth::id()),
 * and anything that touches members is limited to members of the admin's district.
 */
class DistrictAdminContentController extends Controller
{
    private function myDistrictId()
    {
        return DistrictAdmin::where('user_id', Auth::id())->value('district_id');
    }

    // ═════════════════════════════════
    // NEWS
    // ═════════════════════════════════
    // ═════════════════════════════════
    // NEWS
    // Replace the existing NEWS block in DistrictAdminContentController with this.
    // ═════════════════════════════════
    public function news()
    {
        return view('district-admin.news', [
            'news' => News::with('createdBy')->where('created_by', Auth::id())->latest()->paginate(10),
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
            return back()->with('error', 'Something went wrong while creating the news. Please try again.')->withInput();
        }

        return back()->with('success', 'News created successfully!');
    }

    public function updateNews(Request $request)
    {
        // Ownership check first: only the creator can edit
        $news = News::where('id', $request->id)->where('created_by', Auth::id())->firstOrFail();

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
            if ($newImage && $oldImage) Storage::disk('public')->delete($oldImage);
        } catch (\Exception $e) {
            Log::error('News update failed: ' . $e->getMessage());
            if ($newImage) Storage::disk('public')->delete($newImage);
            return back()->with('error', 'Something went wrong while updating the news. Please try again.')->withInput();
        }

        return back()->with('success', 'News updated successfully!');
    }

    public function toggleNewsStatus(Request $request)
    {
        $request->validate(['id' => 'required|exists:news,id']);

        $news = News::where('id', $request->id)->where('created_by', Auth::id())->firstOrFail();

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
        $news = News::where('id', $request->id)->where('created_by', Auth::id())->firstOrFail();
        if ($news->image) Storage::disk('public')->delete($news->image);
        $news->delete();

        return back()->with('success', 'News deleted successfully!');
    }

        // ═════════════════════════════════
    // EVENTS
    // ═════════════════════════════════
    public function events()
    {
        // user_ids of members in MY district
        $districtUserIds = MemberProfile::where('district_id', $this->myDistrictId())->select('user_id');

        return view('district-admin.events', [
            'events' => Event::with('createdBy')
                ->withCount(['registrations' => fn($q) => $q->whereIn('user_id', $districtUserIds)])
                ->where('created_by', Auth::id())
                ->latest()->paginate(10),
        ]);
    }

    private function eventRules(bool $isNew): array
    {
        return [
            'title'                 => 'required|string|max:200',
            'description'           => 'required|string',
            'event_date'            => $isNew ? 'required|date|after_or_equal:today' : 'required|date',
            'event_time'            => 'required',
            'location'              => 'nullable|string',
            'total_seats'           => 'required|integer|min:1|max:1000',
            'registration_deadline' => 'nullable|date|before_or_equal:event_date',
            'image'                 => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ];
    }

    private function eventMessages(): array
    {
        return [
            'title.max'                             => 'Event title cannot exceed 200 characters.',
            'event_date.after_or_equal'             => 'Event date cannot be in the past.',
            'total_seats.min'                       => 'Total seats must be at least 1.',
            'total_seats.max'                       => 'Total seats cannot exceed 1,000.',
            'registration_deadline.before_or_equal' => 'Registration deadline must be on or before the event date.',
        ];
    }

    public function storeEvent(Request $request)
    {
        $request->validate($this->eventRules(true), $this->eventMessages());

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $imagePath = $image->storeAs('event_images', 'event_' . time() . '_' . $image->getClientOriginalName(), 'public');
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
        $request->validate(['id' => 'required|exists:events,id'] + $this->eventRules(false), $this->eventMessages());

        $event = Event::where('id', $request->id)->where('created_by', Auth::id())->firstOrFail();

        if ($request->hasFile('image')) {
            if ($event->image) Storage::disk('public')->delete($event->image);
            $image        = $request->file('image');
            $event->image = $image->storeAs('event_images', 'event_' . time() . '_' . $image->getClientOriginalName(), 'public');
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

        $event = Event::where('id', $request->id)->where('created_by', Auth::id())->firstOrFail();
        $event->status = $request->status;
        $event->save();

        return back()->with('success', 'Event status updated to ' . $request->status . '!');
    }

    public function deleteEvent(Request $request)
    {
        $event = Event::where('id', $request->id)->where('created_by', Auth::id())->firstOrFail();
        if ($event->image) Storage::disk('public')->delete($event->image);
        $event->delete();

        return back()->with('success', 'Event deleted successfully!');
    }

    public function eventDetail($id)
    {
        $event = Event::where('id', $id)->where('created_by', Auth::id())->first();
        if (!$event) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'title'          => $event->title,
            'description'    => $event->description,
            'event_date_raw' => $event->event_date->format('Y-m-d'),
            'event_time'     => $event->event_time,
            'location'       => $event->location,
            'total_seats'    => $event->total_seats,
            'deadline_raw'   => $event->registration_deadline ? $event->registration_deadline->format('Y-m-d') : null,
        ]);
    }

    public function eventRegistrations($id)
    {
        $event = Event::where('id', $id)->where('created_by', Auth::id())->firstOrFail();

        // members of MY district, keyed by user_id
        $profiles = MemberProfile::with(['unit', 'district'])
            ->where('district_id', $this->myDistrictId())
            ->get()
            ->keyBy('user_id');

        $registrations = EventRegistration::with('user')
            ->where('event_id', $id)
            ->whereIn('user_id', $profiles->keys())
            ->get()
            ->each(fn($reg) => $reg->setRelation('memberProfile', $profiles->get($reg->user_id)));

        return view('district-admin.event-registrations', compact('event', 'registrations'));
    }

    // ═════════════════════════════════
    // FUNDRAISING
    // ═════════════════════════════════
    public function fundraising()
    {
        return view('district-admin.fundraising', [
            'campaigns' => FundraisingCampaign::where('created_by', Auth::id())->latest()->paginate(20),
        ]);
    }

    private function campaignRules(bool $isNew): array
    {
        return [
            'title'         => 'required|string|max:200',
            'description'   => 'required|string',
            'target_amount' => ['required', 'numeric', 'min:1', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'raised_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'end_date'      => $isNew ? 'nullable|date|after_or_equal:today' : 'nullable|date',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png|max:5120',
        ];
    }

    private function campaignMessages(): array
    {
        return [
            'title.max'           => 'Title cannot exceed 200 characters.',
            'target_amount.max'   => 'Target amount cannot exceed 7 digits (max ₹99,99,999).',
            'target_amount.regex' => 'Enter a valid amount with up to 7 digits.',
            'raised_amount.max'   => 'Raised amount cannot exceed 7 digits (max ₹99,99,999).',
            'raised_amount.regex' => 'Enter a valid amount with up to 7 digits.',
        ];
    }

    public function storeFundraisingCampaign(Request $request)
    {
        $request->validate($this->campaignRules(true), $this->campaignMessages());

        $imagePath = null;
        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $imagePath = $image->storeAs('campaign_images', 'campaign_' . time() . '_' . $image->getClientOriginalName(), 'public');
        }

        FundraisingCampaign::create([
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
        $request->validate(['id' => 'required|exists:fundraising_campaigns,id'] + $this->campaignRules(false), $this->campaignMessages());

        $campaign = FundraisingCampaign::where('created_by', Auth::id())->findOrFail($request->id);

        if ($request->hasFile('image')) {
            if ($campaign->image) Storage::disk('public')->delete($campaign->image);
            $image           = $request->file('image');
            $campaign->image = $image->storeAs('campaign_images', 'campaign_' . time() . '_' . $image->getClientOriginalName(), 'public');
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

        $campaign = FundraisingCampaign::where('created_by', Auth::id())->findOrFail($request->id);
        $campaign->status = $request->status;
        $campaign->save();

        return back()->with('success', 'Campaign status updated to ' . $request->status . '!');
    }

    public function deleteFundraisingCampaign(Request $request)
    {
        $campaign = FundraisingCampaign::where('created_by', Auth::id())->findOrFail($request->id);
        if ($campaign->image) Storage::disk('public')->delete($campaign->image);
        $campaign->delete();

        return back()->with('success', 'Campaign deleted successfully!');
    }

    public function fundraisingCampaignDetail($id)
    {
        $campaign = FundraisingCampaign::where('created_by', Auth::id())->find($id);
        if (!$campaign) return response()->json(['error' => 'Not found'], 404);

        return response()->json([
            'title'         => $campaign->title,
            'description'   => $campaign->description,
            'target_amount' => $campaign->target_amount,
            'raised_amount' => $campaign->raised_amount,
            'end_date_raw'  => $campaign->end_date ? $campaign->end_date->format('Y-m-d') : null,
            'status'        => $campaign->status,
        ]);
    }

    // ═════════════════════════════════
    // COMMITTEES  (members come from the whole district)
    // ═════════════════════════════════
    public function committees()
    {
        return view('district-admin.committees', [
            'committees' => Committee::withCount('members')->where('created_by', Auth::id())->latest()->paginate(20),
        ]);
    }

    public function storeCommittee(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ], ['name.max' => 'Committee name cannot exceed 200 characters.']);

        Committee::create([
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
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string',
            'start_date'  => 'required|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
        ], ['name.max' => 'Committee name cannot exceed 200 characters.']);

        Committee::where('created_by', Auth::id())->findOrFail($request->id)->update([
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

        $committee = Committee::where('created_by', Auth::id())->findOrFail($request->id);
        $committee->is_active = !$committee->is_active;
        $committee->save();

        return back()->with('success', 'Committee ' . ($committee->is_active ? 'activated' : 'deactivated') . '!');
    }

    public function deleteCommittee(Request $request)
    {
        Committee::where('created_by', Auth::id())->findOrFail($request->id)->delete();
        return back()->with('success', 'Committee deleted successfully!');
    }

    public function committeeDetail($id)
    {
        $committee = Committee::where('created_by', Auth::id())->find($id);
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
        $committee = Committee::with(['members.user'])->where('created_by', Auth::id())->find($id);

        if (!$committee) {
            return redirect()->route('district-admin.committees')->with('error', 'Committee not found!');
        }

        $availableMembers = MemberProfile::with('user')
            ->where('district_id', $this->myDistrictId())
            ->where('status', 'APPROVED')
            ->whereNotIn('id', $committee->members->pluck('id'))
            ->get();

        return view('district-admin.committee-members', [
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

        $committee = Committee::where('created_by', Auth::id())->findOrFail($request->committee_id);
        $member    = MemberProfile::where('district_id', $this->myDistrictId())->findOrFail($request->member_profile_id);

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

        $committee = Committee::where('created_by', Auth::id())->findOrFail($request->committee_id);
        $committee->members()->updateExistingPivot($request->member_profile_id, ['role' => $request->role]);

        return back()->with('success', 'Member role updated!');
    }

    public function removeCommitteeMember(Request $request)
    {
        $request->validate([
            'committee_id'      => 'required|exists:committees,id',
            'member_profile_id' => 'required|exists:member_profiles,id',
        ]);

        $committee = Committee::where('created_by', Auth::id())->findOrFail($request->committee_id);
        $committee->members()->detach($request->member_profile_id);

        return back()->with('success', 'Member removed from committee.');
    }

    // ═════════════════════════════════
    // SCHEMES  (members from the whole district)
    // ═════════════════════════════════
    public function schemes()
    {
        $districtId = $this->myDistrictId();

        return view('district-admin.schemes', [
            'schemes' => Scheme::where('created_by', Auth::id())
                ->withCount([
                    'dues as dues_count' => fn($q) => $q->whereHas('memberProfile', fn($q2) => $q2->where('district_id', $districtId)),
                    'members as members_count' => fn($q) => $q->where('district_id', $districtId),
                ])
                ->latest()->paginate(20),
            'allMembers' => MemberProfile::with(['user', 'unit'])
                ->where('district_id', $districtId)
                ->where('status', 'APPROVED')
                ->get(),
        ]);
    }

    private function schemeRules(): array
    {
        return [
            'title'           => 'required|string|max:200',
            'description'     => 'nullable|string',
            'amount'          => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'duration_months' => 'required|integer|min:1|max:360',
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
            'member_ids'      => 'required|array|min:1',
            'member_ids.*'    => 'exists:member_profiles,id',
        ];
    }

    private function schemeMessages(): array
    {
        return [
            'member_ids.required'  => 'Select at least one member for this scheme.',
            'title.max'            => 'Title cannot exceed 200 characters.',
            'amount.max'           => 'Amount cannot exceed 7 digits (max ₹99,99,999).',
            'amount.regex'         => 'Enter a valid amount with up to 7 digits.',
            'duration_months.max'  => 'Duration cannot exceed 360 months (30 years).',
        ];
    }

    public function storeScheme(Request $request)
    {
        $request->validate($this->schemeRules(), $this->schemeMessages());

        $validMemberIds = MemberProfile::where('district_id', $this->myDistrictId())
            ->whereIn('id', $request->member_ids)->pluck('id');

        $scheme = Scheme::create([
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
        $request->validate(['id' => 'required|exists:schemes,id'] + $this->schemeRules(), $this->schemeMessages());

        $scheme = Scheme::where('created_by', Auth::id())->findOrFail($request->id);

        $scheme->update([
            'title'           => $request->title,
            'description'     => $request->description,
            'amount'          => $request->amount,
            'duration_months' => $request->duration_months,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
        ]);

        $validMemberIds = MemberProfile::where('district_id', $this->myDistrictId())
            ->whereIn('id', $request->member_ids)->pluck('id');

        $scheme->members()->sync($validMemberIds);

        return back()->with('success', 'Scheme updated successfully! Now applies to ' . $validMemberIds->count() . ' selected member(s).');
    }

    public function schemeDetail($id)
    {
        $districtId = $this->myDistrictId();

        $scheme = Scheme::with(['members' => fn($q) => $q->where('district_id', $districtId)->with('user')])
            ->where('created_by', Auth::id())->find($id);

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
    'members'         => $scheme->members->map(fn($m) => [
        'id'    => $m->id,
        'name'  => $m->user->name ?? '—',
        'email' => $m->user->email ?? '—',
        'unit'  => $m->unit->name ?? null,
    ]),
]);
    }

    public function toggleSchemeStatus(Request $request)
    {
        $request->validate(['id' => 'required|exists:schemes,id']);

        $scheme = Scheme::where('created_by', Auth::id())->findOrFail($request->id);
        $scheme->is_active = !$scheme->is_active;
        $scheme->save();

        return back()->with('success', 'Scheme ' . ($scheme->is_active ? 'activated' : 'deactivated') . '!');
    }

    public function deleteScheme(Request $request)
    {
        $request->validate(['id' => 'required|exists:schemes,id']);

        $scheme = Scheme::where('created_by', Auth::id())->findOrFail($request->id);

        if ($scheme->dues()->where('status', 'PAID')->exists()) {
            return back()->with('error', 'Cannot delete! Some members have already paid dues under this scheme. Deactivate it instead.');
        }

        $scheme->delete();
        return back()->with('success', 'Scheme deleted successfully!');
    }

    public function schemePayers($id)
    {
        $districtId = $this->myDistrictId();
        $scheme = Scheme::where('created_by', Auth::id())->find($id);

        if (!$scheme) {
            return redirect()->route('district-admin.schemes')->with('error', 'Scheme not found!');
        }

        $dues = $scheme->dues()
            ->whereHas('memberProfile', fn($q) => $q->where('district_id', $districtId))
            ->with(['memberProfile.user', 'memberProfile.unit'])
            ->get();

        return view('district-admin.scheme-payers', ['scheme' => $scheme, 'dues' => $dues]);
    }

    // ═════════════════════════════════
    // OTHER FEES
    // ═════════════════════════════════
    public function otherFees()
    {
        $districtId = $this->myDistrictId();

        return view('district-admin.other-fees', [
            'fees' => OtherFee::where('created_by', Auth::id())
                ->withCount(['payments as payments_count' => fn($q) =>
                    $q->whereHas('memberProfile', fn($q2) => $q2->where('district_id', $districtId))])
                ->latest()->paginate(20),
        ]);
    }

    private function feeRules(): array
    {
        return [
            'title'    => 'required|string|max:200',
            'amount'   => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'due_date' => 'nullable|date|after_or_equal:today',
        ];
    }

    private function feeMessages(): array
    {
        return [
            'title.max'               => 'Title cannot exceed 200 characters.',
            'amount.max'              => 'Amount cannot exceed 7 digits (max ₹99,99,999).',
            'amount.regex'            => 'Enter a valid amount with up to 7 digits.',
            'due_date.date'           => 'Please enter a valid date.',
            'due_date.after_or_equal' => 'Due date cannot be in the past.',
        ];
    }

    public function storeOtherFee(Request $request)
    {
        $request->validate($this->feeRules(), $this->feeMessages());

        OtherFee::create([
            'title'      => $request->title,
            'amount'     => $request->amount,
            'due_date'   => $request->due_date,
            'created_by' => Auth::id(),
            'is_active'  => true,
        ]);

        return back()->with('success', 'Other fee created successfully! It will apply to your district\'s active members.');
    }

    public function updateOtherFee(Request $request)
    {
        $request->validate(['id' => 'required|exists:other_fees,id'] + $this->feeRules(), $this->feeMessages());

        OtherFee::where('created_by', Auth::id())->findOrFail($request->id)->update([
            'title'    => $request->title,
            'amount'   => $request->amount,
            'due_date' => $request->due_date,
        ]);

        return back()->with('success', 'Other fee updated successfully!');
    }

    public function toggleOtherFeeStatus(Request $request)
    {
        $request->validate(['id' => 'required|exists:other_fees,id']);

        $fee = OtherFee::where('created_by', Auth::id())->findOrFail($request->id);
        $fee->is_active = !$fee->is_active;
        $fee->save();

        return back()->with('success', 'Fee ' . ($fee->is_active ? 'activated' : 'deactivated') . '! ' . ($fee->is_active
            ? 'It will now apply to members who check their dues.'
            : 'No new members will be charged this fee.'));
    }

    public function deleteOtherFee(Request $request)
    {
        $fee = OtherFee::where('created_by', Auth::id())->findOrFail($request->id);

        if ($fee->payments()->where('status', 'PAID')->exists()) {
            return back()->with('error', 'Cannot delete! Some members have already paid this fee. Deactivate it instead.');
        }

        $fee->delete();
        return back()->with('success', 'Other fee deleted successfully!');
    }

    public function otherFeeDetail($id)
    {
        $fee = OtherFee::where('created_by', Auth::id())->find($id);
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
        $districtId = $this->myDistrictId();
        $fee = OtherFee::where('created_by', Auth::id())->find($id);

        if (!$fee) {
            return redirect()->route('district-admin.other-fees')->with('error', 'Fee not found!');
        }

        $payments = $fee->payments()
            ->whereHas('memberProfile', fn($q) => $q->where('district_id', $districtId))
            ->with(['memberProfile.user', 'memberProfile.unit'])
            ->get();

        return view('district-admin.other-fee-payers', ['fee' => $fee, 'payments' => $payments]);
    }

    // ═════════════════════════════════
    // GALLERY
    // ═════════════════════════════════
    public function gallery()
    {
        return view('district-admin.gallery', [
            'images' => GalleryImage::where('created_by', Auth::id())->latest()->paginate(24),
        ]);
    }

    public function storeGalleryImages(Request $request)
    {
        $request->validate([
            'images'   => 'required|array|min:1',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        foreach ($request->file('images') as $file) {
            $path = $file->storeAs('gallery_images', 'gallery_' . time() . '_' . uniqid() . '_' . $file->getClientOriginalName(), 'public');

            GalleryImage::create([
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

        $image = GalleryImage::where('created_by', Auth::id())->findOrFail($request->id);
        $image->is_active = !$image->is_active;
        $image->save();

        return back()->with('success', 'Image ' . ($image->is_active ? 'shown in' : 'hidden from') . ' the app gallery.');
    }

    public function deleteGalleryImage(Request $request)
    {
        $image = GalleryImage::where('created_by', Auth::id())->findOrFail($request->id);
        if ($image->image) Storage::disk('public')->delete($image->image);
        $image->delete();

        return back()->with('success', 'Image deleted successfully!');
    }

    // ═════════════════════════════════
    // EXPENSES
    // NOTE: unit_id is null (same as the super admin's "global" expenses).
    // If members should only see their own district's expenses, that needs a
    // district_id column on `expenses` and a filter in the member-facing API.
    // ═════════════════════════════════
    public function expenses()
    {
        $today = Carbon::today();
        $mine  = fn() => Expense::where('created_by', Auth::id());

        return view('district-admin.expenses', [
            'expenses'   => $mine()->latest('expense_date')->paginate(20),
            'categories' => ['Maintenance', 'Events', 'Utilities', 'Cleaning', 'Stationery', 'Others'],

            'todaysExpense'     => $mine()->whereDate('expense_date', $today)->sum('amount'),
            'thisMonthExpense'  => $mine()->whereYear('expense_date', $today->year)->whereMonth('expense_date', $today->month)->sum('amount'),
            'thisYearExpense'   => $mine()->whereYear('expense_date', $today->year)->sum('amount'),
            'totalTransactions' => $mine()->whereYear('expense_date', $today->year)->count(),

            'categoryTotals' => $mine()->whereYear('expense_date', $today->year)->whereMonth('expense_date', $today->month)
                ->selectRaw('category, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('category')->orderByDesc('total')->get(),

            'monthlyTotals' => $mine()->whereYear('expense_date', $today->year)
                ->selectRaw('MONTH(expense_date) as month, SUM(amount) as total, COUNT(*) as count')
                ->groupBy('month')->orderByDesc('month')->get(),
        ]);
    }

    private function expenseRules(): array
    {
        return [
            'title'        => 'required|string|max:200',
            'category'     => 'required|string|max:100',
            'amount'       => ['required', 'numeric', 'min:0.01', 'max:9999999', 'regex:/^\d{1,7}(\.\d{1,2})?$/'],
            'expense_date' => 'required|date',
            'expense_time' => 'nullable|date_format:H:i',
            'paid_to'      => 'nullable|string|max:150',
            'status'       => 'required|in:PENDING,APPROVED,PAID,REJECTED',
        ];
    }

    public function storeExpense(Request $request)
    {
        $request->validate($this->expenseRules(), ['amount.regex' => 'Enter a valid amount with up to 7 digits.']);

        Expense::create([
            'title'        => $request->title,
            'category'     => $request->category,
            'amount'       => $request->amount,
            'expense_date' => $request->expense_date,
            'expense_time' => $request->expense_time ?: now()->format('H:i'),
            'paid_to'      => $request->paid_to,
            'status'       => $request->status,
            'created_by'   => Auth::id(),
            'unit_id'      => null,
        ]);

        return back()->with('success', 'Expense recorded successfully!');
    }

    public function updateExpense(Request $request)
    {
        $request->validate(['id' => 'required|exists:expenses,id'] + $this->expenseRules(),
            ['amount.regex' => 'Enter a valid amount with up to 7 digits.']);

        Expense::where('created_by', Auth::id())->findOrFail($request->id)->update($request->only([
            'title', 'category', 'amount', 'expense_date', 'expense_time', 'paid_to', 'status',
        ]));

        return back()->with('success', 'Expense updated successfully!');
    }

    public function deleteExpense(Request $request)
    {
        $request->validate(['id' => 'required|exists:expenses,id']);
        Expense::where('created_by', Auth::id())->findOrFail($request->id)->delete();

        return back()->with('success', 'Expense deleted successfully!');
    }

    public function expenseDetail($id)
    {
        $expense = Expense::where('created_by', Auth::id())->find($id);
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
}