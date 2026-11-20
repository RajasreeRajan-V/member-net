<?php

namespace App\Services;

use App\Models\MemberProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MembershipService
{
    public function approve(MemberProfile $profile): MemberProfile
    {
        return DB::transaction(function () use ($profile) {
            $profile = MemberProfile::with(['user', 'unit', 'district'])
                ->lockForUpdate()
                ->findOrFail($profile->id);

            // An ID never changes once issued (e.g. rejected, then re-approved)
            if (!$profile->membership_id) {
                $profile->membership_id = $this->nextMembershipId($profile);
            }

            $profile->status      = 'APPROVED';
            $profile->joined_date = $profile->joined_date ?? Carbon::now()->toDateString();
            $profile->save();

            return $profile;
        });
    }

    // Format: MemberNet/HOS/004 (serial per unit)
private function nextMembershipId(MemberProfile $profile): string
{
    $profile->loadMissing(['unit.district', 'district']);

    // Unit-based code (e.g. ALU for Aluva, HOS for Hosdurg)
    $code = strtoupper(substr($profile->unit?->name ?? $profile->district?->name ?? 'MEM', 0, 3));

    $newPrefix   = 'MemberNet/' . $code . '/';
    $fummaPrefix = 'FuMMA/' . $code . '/';
    $prevPrefix  = 'FUMMA/' . $code . '/';
    $oldPrefix   = 'FUMMA-' . $code . '-';

    $distCode = strtoupper(substr($profile->unit?->district?->name ?? $profile->district?->name ?? '', 0, 3));

    $maxSerial = MemberProfile::withTrashed()   // remove withTrashed() if no SoftDeletes
        ->whereNotNull('membership_id')
        ->where(function ($q) use ($newPrefix, $fummaPrefix, $prevPrefix, $oldPrefix, $distCode, $code) {
            $q->where('membership_id', 'LIKE', $newPrefix . '%')
              ->orWhere('membership_id', 'LIKE', $fummaPrefix . '%')
              ->orWhere('membership_id', 'LIKE', $prevPrefix . '%')
              ->orWhere('membership_id', 'LIKE', $oldPrefix . '%');

            if ($distCode && $distCode !== $code) {
                $q->orWhere('membership_id', 'LIKE', 'MemberNet/' . $distCode . '/%')
                  ->orWhere('membership_id', 'LIKE', 'FuMMA/' . $distCode . '/%')
                  ->orWhere('membership_id', 'LIKE', 'FUMMA/' . $distCode . '/%');
            }
        })
        ->lockForUpdate()
        ->selectRaw("MAX(CAST(SUBSTRING_INDEX(REPLACE(membership_id, '/', '-'), '-', -1) AS UNSIGNED)) as max_serial")
        ->value('max_serial');

    return $newPrefix . str_pad(((int) $maxSerial) + 1, 3, '0', STR_PAD_LEFT);
}
}