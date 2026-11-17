<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\News;
use App\Models\User;
use App\Models\MemberProfile;
use App\Models\DistrictAdmin;
use App\Models\UnitAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class NewsController extends Controller
{
    // ─────────────────────────────────
    // HELPER: published news visible to the logged-in member
    // = global (super admin) + own district admin's + own unit admin's
    // ─────────────────────────────────
    private function visibleNewsQuery(Request $request)
    {
        $profile = MemberProfile::where('user_id', $request->user()->id)->first();

        // Every district/unit admin in the system (their news is NOT global)
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

        return News::where('status', 'PUBLISHED')
            ->where(function ($q) use ($scopedAdminIds, $myDistrictAdminIds, $myUnitAdminIds) {
                // Global: creator still exists and is not any district/unit admin
                $q->whereHas('createdBy', fn($u) => $u->whereNotIn('id', $scopedAdminIds))
                  // Own district's admin
                  ->orWhereIn('created_by', $myDistrictAdminIds)
                  // Own unit's admin
                  ->orWhereIn('created_by', $myUnitAdminIds);
            });
    }

    // ─────────────────────────────────
    // HELPER: ids (from the given list) this member has already read
    // ─────────────────────────────────
    private function readIdsFor(int $userId, $newsIds): array
    {
        return \App\Models\NewsRead::where('user_id', $userId)
            ->whereIn('news_id', $newsIds)
            ->pluck('news_id')
            ->all();
    }

    // is_read: "1" = member has read it, "0" = not read yet
    private function formatNews($n, array $readIds = []): array
    {
        return [
            'id'           => (string) $n->id,
            'title'        => $n->title,
            'content'      => $n->content,
            'image'        => $n->image ? asset('storage/' . $n->image) : null,
            'status'       => $n->status,
            'is_read'      => in_array($n->id, $readIds) ? '1' : '0',
            'published_at' => $n->published_at?->format('d M Y h:i A'),
            'created_at'   => (string) $n->created_at,
        ];
    }

    // ─────────────────────────────────
    // GET PUBLISHED NEWS (Member)
    // GET /api/news
    // ─────────────────────────────────
    public function getPublishedNews(Request $request)
    {
        $request->validate([
            'search'   => 'nullable|string|max:255',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $news = $this->visibleNewsQuery($request)
                    ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
                    ->latest('published_at')
                    ->paginate($request->per_page ?? 10);

        $readIds = $this->readIdsFor(
            $request->user()->id,
            $news->getCollection()->pluck('id')
        );

        return ApiResponse::success([
            'data'         => $news->getCollection()->map(fn($n) => $this->formatNews($n, $readIds)),
            'current_page' => $news->currentPage(),
            'per_page'     => $news->perPage(),
            'total'        => $news->total(),
            'last_page'    => $news->lastPage(),
            'has_more'     => $news->hasMorePages(),
        ], 'News fetched successfully');
    }

    // ─────────────────────────────────
    // GET SINGLE NEWS DETAIL (Member)
    // POST /api/news/detail
    // ─────────────────────────────────
    public function getNewsDetail(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:news,id'
        ]);

        // News from other districts/units is treated as "not found"
        $news = $this->visibleNewsQuery($request)
                    ->where('id', $request->id)
                    ->first();

        if (!$news) {
            return ApiResponse::error('News not found.', 404);
        }

        $readIds = $this->readIdsFor($request->user()->id, [$news->id]);

        return ApiResponse::success(
            $this->formatNews($news, $readIds),
            'News detail fetched successfully'
        );
    }

    // ─────────────────────────────────
    // GET ALL NEWS (Super Admin)
    // GET /api/admin/news
    // read_count = how many members have read each news
    // ─────────────────────────────────
    public function getAllNews()
    {
        $news = News::with('createdBy')
                    ->withCount('reads')
                    ->latest()
                    ->get();

        $data = $news->map(function ($n) {
            return [
                'id'           => (string) $n->id,
                'title'        => $n->title,
                'content'      => $n->content,
                'image'        => $n->image
                    ? asset('storage/' . $n->image)
                    : null,
                'status'       => $n->status,
                'created_by'   => $n->createdBy?->name,
                'read_count'   => (string) $n->reads_count,
                'published_at' => $n->published_at
                    ? $n->published_at->format('d M Y h:i A')
                    : null,
                'created_at'   => (string) $n->created_at,
            ];
        });

        return ApiResponse::success(
            $data,
            'News fetched successfully'
        );
    }

    // ─────────────────────────────────
    // CREATE NEWS (Super Admin)
    // POST /api/admin/news
    // ─────────────────────────────────
    public function createNews(Request $request)
    {
        $request->validate([
            'title'   => 'required|string',
            'content' => 'required|string',
            'status'  => 'required|in:DRAFT,PUBLISHED',
            'image'   => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $image     = $request->file('image');
            $fileName  = 'news_' . time() . '_'
                         . $image->getClientOriginalName();
            $imagePath = $image->storeAs(
                'news_images', $fileName, 'public'
            );
        }

        $news = News::create([
            'created_by'   => auth()->id(),
            'title'        => $request->input('title'),
            'content'      => $request->input('content'),
            'image'        => $imagePath,
            'status'       => $request->input('status'),
            'published_at' => $request->status === 'PUBLISHED'
                ? Carbon::now()
                : null,
        ]);

        return ApiResponse::success(
            [
                'id'           => (string) $news->id,
                'title'        => $news->title,
                'content'      => $news->content,
                'image'        => $news->image
                    ? asset('storage/' . $news->image)
                    : null,
                'status'       => $news->status,
                'published_at' => $news->published_at
                    ? $news->published_at->format('d M Y h:i A')
                    : null,
            ],
            'News created successfully',
            201
        );
    }

    // ─────────────────────────────────
    // UPDATE NEWS (Super Admin)
    // POST /api/admin/news/update
    // ─────────────────────────────────
    public function updateNews(Request $request)
    {
        $request->validate([
            'id'      => 'required|exists:news,id',
            'title'   => 'sometimes|string',
            'content' => 'sometimes|string',
            'image'   => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $news = News::find($request->id);

        // Handle new image upload
        if ($request->hasFile('image')) {
            // Delete old image first
            if ($news->image) {
                Storage::disk('public')->delete($news->image);
            }
            $image       = $request->file('image');
            $fileName    = 'news_' . time() . '_'
                           . $image->getClientOriginalName();
            $news->image = $image->storeAs(
                'news_images', $fileName, 'public'
            );
        }

        if ($request->filled('title')) {
            $news->title = $request->input('title');
        }

        if ($request->filled('content')) {
            $news->content = $request->input('content');
        }

        $news->save();

        return ApiResponse::success(
            [
                'id'      => (string) $news->id,
                'title'   => $news->title,
                'content' => $news->content,
                'image'   => $news->image
                    ? asset('storage/' . $news->image)
                    : null,
                'status'  => $news->status,
            ],
            'News updated successfully'
        );
    }

    // ─────────────────────────────────
    // TOGGLE STATUS (Super Admin)
    // PUT /api/admin/news/toggle-status
    // DRAFT → PUBLISHED / PUBLISHED → DRAFT
    // ─────────────────────────────────
    public function toggleStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:news,id'
        ]);

        $news = News::find($request->id);

        if ($news->status === 'DRAFT') {
            $news->status       = 'PUBLISHED';
            $news->published_at = Carbon::now();
        } else {
            $news->status       = 'DRAFT';
            $news->published_at = null;
        }

        $news->save();

        $action = $news->status === 'PUBLISHED'
            ? 'published'
            : 'unpublished';

        return ApiResponse::success(
            [
                'id'           => (string) $news->id,
                'title'        => $news->title,
                'status'       => $news->status,
                'published_at' => $news->published_at
                    ? $news->published_at->format('d M Y h:i A')
                    : null,
            ],
            'News ' . $action . ' successfully'
        );
    }

    // ─────────────────────────────────
    // DELETE NEWS (Super Admin)
    // DELETE /api/admin/news
    // ─────────────────────────────────
    public function deleteNews(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:news,id'
        ]);

        $news = News::find($request->id);

        // Delete image if exists
        if ($news->image) {
            Storage::disk('public')->delete($news->image);
        }

        $news->delete();

        return ApiResponse::success(
            null,
            'News deleted successfully'
        );
    }

    // ─────────────────────────────────
    // MARK NEWS AS READ (Member)
    // Body: { news_id }
    // ─────────────────────────────────
    public function markRead(Request $request)
    {
        $request->validate(['news_id' => 'required|exists:news,id']);

        $userId  = $request->user()->id;
        $service = app(\App\Services\ContentVisibility::class);

        // Only news this member is allowed to see can be marked read
        $visible = $service->news($userId)->where('id', $request->news_id)->exists();
        if (!$visible) {
            return ApiResponse::error('News not found.', 404);
        }

        \App\Models\NewsRead::firstOrCreate(
            ['user_id' => $userId, 'news_id' => $request->news_id],
            ['read_at' => now()]
        );

        return ApiResponse::success(
            ['unread_news_count' => (string) $service->unreadNewsCount($userId)],
            'News marked as read'
        );
    }
}