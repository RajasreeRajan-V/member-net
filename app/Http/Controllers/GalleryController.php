<?php
namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\GalleryImage;
use App\Models\MemberProfile;
use App\Models\DistrictAdmin;
use App\Models\UnitAdmin;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    // ─────────────────────────────────
    // HELPER: gallery images visible to the logged-in member
    // = global (super admin) + own district admin's + own unit admin's
    // ─────────────────────────────────
    private function visibleGalleryQuery(Request $request)
    {
        $profile = MemberProfile::where('user_id', $request->user()->id)->first();

        // Every district/unit admin in the system (their images are NOT global)
        $scopedAdminIds = DistrictAdmin::pluck('user_id')
            ->merge(UnitAdmin::pluck('user_id'))
            ->unique()
            ->values()
            ->all();

        // Only this member's own district admin / unit admin
        $myDistrictAdminIds = $profile
            ? DistrictAdmin::where('district_id', $profile->district_id)->pluck('user_id')->all()
            : [];

        $myUnitAdminIds = $profile
            ? UnitAdmin::where('unit_id', $profile->unit_id)->pluck('user_id')->all()
            : [];

        return GalleryImage::where('is_active', true)
            ->where(function ($q) use ($scopedAdminIds, $myDistrictAdminIds, $myUnitAdminIds) {
                // Global: created by anyone who is NOT a district/unit admin (super admin)
                $q->whereNotIn('created_by', $scopedAdminIds)
                  // Own district's admin
                  ->orWhereIn('created_by', $myDistrictAdminIds)
                  // Own unit's admin
                  ->orWhereIn('created_by', $myUnitAdminIds);
            });
    }

    // ─────────────────────────────────
    // GET /api/gallery?page=1&limit=20
    // Requires: Authorization: Bearer {token}
    // ─────────────────────────────────
    public function index(Request $request)
    {
        $perPage = max(1, (int) $request->query('limit', 20));
        $page    = max(1, (int) $request->query('page', 1));

        $paginated = $this->visibleGalleryQuery($request)
    ->latest()
    ->latest('id')          // stable order if two images share the same second
    ->paginate($perPage, ['*'], 'page', $page);

$items = $paginated->getCollection()->map(fn ($g) => [
    'id'         => 'p' . $g->id,
    'image'      => asset('storage/' . $g->image),
    // UTC with timezone, so the app can send this exact value back
    'created_at' => $g->created_at->copy()->utc()->toIso8601String(),
]);

return ApiResponse::success([
    'items'       => $items,
    'server_time' => now()->utc()->toIso8601String(),
    'pagination'  => [
        'current_page'  => $paginated->currentPage(),
        'total_pages'   => $paginated->lastPage(),
        'total_records' => $paginated->total(),
        'limit'         => $perPage,
    ],
], 'Gallery fetched successfully.');
    }
    public function seen(Request $request)
{
    $request->validate(['last_seen_at' => 'required|date']);

    $userId  = $request->user()->id;
    $profile = MemberProfile::where('user_id', $userId)->first();

    if (!$profile) {
        return ApiResponse::error('Profile not found.', 404);
    }

    // The app sends the time it FETCHED the gallery, so images uploaded
    // while the screen was open are not lost. Convert to server timezone,
    // never accept a future time, and never move backwards.
    $seenAt = \Carbon\Carbon::parse($request->last_seen_at)
        ->setTimezone(config('app.timezone'));

    if ($seenAt->isFuture()) {
        $seenAt = now();
    }

    if (!$profile->gallery_last_seen_at || $seenAt->gt($profile->gallery_last_seen_at)) {
        $profile->gallery_last_seen_at = $seenAt;
        $profile->save();
    }

   return ApiResponse::success(
    ['unread_gallery_count' => (string) app(\App\Services\ContentVisibility::class)->unreadGalleryCount($userId)],
    'Gallery marked as seen'
);
}
}