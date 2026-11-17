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

    // Format: FuMMA/KAS/004 (serial per district code)
private function nextMembershipId(MemberProfile $profile): string
{
    $districtCode = strtoupper(substr($profile->district->name ?? 'XXX', 0, 3));

    $newPrefix  = 'FuMMA/' . $districtCode . '/';
    $prevPrefix = 'FUMMA/' . $districtCode . '/';   // IDs already issued in the previous format
    $oldPrefix  = 'FUMMA-' . $districtCode . '-';   // IDs issued in the oldest format

    $maxSerial = MemberProfile::withTrashed()   // remove withTrashed() if no SoftDeletes
        ->whereNotNull('membership_id')
        ->where(function ($q) use ($newPrefix, $prevPrefix, $oldPrefix) {
            $q->where('membership_id', 'LIKE', $newPrefix . '%')
              ->orWhere('membership_id', 'LIKE', $prevPrefix . '%')
              ->orWhere('membership_id', 'LIKE', $oldPrefix . '%');
        })
        ->lockForUpdate()
        ->selectRaw("MAX(CAST(SUBSTRING_INDEX(REPLACE(membership_id, '/', '-'), '-', -1) AS UNSIGNED)) as max_serial")
        ->value('max_serial');

    return $newPrefix . str_pad(((int) $maxSerial) + 1, 3, '0', STR_PAD_LEFT);
}
}