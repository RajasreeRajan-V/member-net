<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\Renewal;
use Illuminate\Http\Request;

class RenewalApiController extends Controller
{
    // GET /api/renewals/status?page=1&limit=20
    // Requires: Authorization: Bearer {token}
    public function status(Request $request)
    {
        $user = auth()->user();
        $memberProfile = $user->memberProfile;

        if (!$memberProfile) {
            return ApiResponse::error('Member profile not found.', 404);
        }

        $perPage = max(1, (int) $request->query('limit', 20));
        $page    = max(1, (int) $request->query('page', 1));

        $baseQuery = Renewal::where('member_profile_id', $memberProfile->id);

        // ── Summary counts computed from the full set (status is an accessor) ──
        $all = (clone $baseQuery)->get();
        $summary = [
            'total'   => (string) $all->count(),
            'active'  => (string) $all->where('status', 'ACTIVE')->count(),
            'expired' => (string) $all->where('status', 'EXPIRED')->count(),
        ];

        $renewals = $baseQuery->latest('valid_until')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = $renewals->getCollection()->map(function ($r) {
            $item = [
                'id'           => 'r' . $r->id,
                'title'        => (string) $r->title,
                'valid_from'   => $r->valid_from->format('Y-m-d'),
                'valid_until'  => $r->valid_until->format('Y-m-d'),
                'renewal_date' => $r->renewal_date
                    ? $r->renewal_date->format('Y-m-d')
                    : $r->valid_until->copy()->addDay()->format('Y-m-d'),
                'status'       => $r->status,
            ];

            // 'type' is only included when set, matching the ID_CARD example
            if ($r->type) {
                $item = [
                    'id'           => $item['id'],
                    'title'        => $item['title'],
                    'type'         => (string) $r->type,
                    'valid_from'   => $item['valid_from'],
                    'valid_until'  => $item['valid_until'],
                    'renewal_date' => $item['renewal_date'],
                    'status'       => $item['status'],
                ];
            }

            return $item;
        });

        return ApiResponse::success([
            'summary'     => $summary,
            'items'       => $items,
            'page'        => (string) $renewals->currentPage(),
            'total_pages' => (string) $renewals->lastPage(),
        ], 'Renewal status fetched successfully.');
    }
}