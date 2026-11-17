<?php
namespace App\Traits;
use App\Models\MemberProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
trait GeneratesMembershipId
{
    private function generateMembershipId(MemberProfile $profile): string
    {
        $profile->loadMissing(['state', 'district']);
        $stateCode    = strtoupper(substr($profile->state->name    ?? 'XX',  0, 2));
        $districtCode = strtoupper(substr($profile->district->name ?? 'XXX', 0, 3));
        $year         = Carbon::now()->year;
        $last = MemberProfile::whereNotNull('membership_id')
            ->whereYear('joined_date', $year)
            ->orderBy('id', 'desc')
            ->first();
        $parts  = ($last && $last->membership_id) ? explode('-', $last->membership_id) : [];
        $serial = !empty($parts) ? (int) end($parts) + 1 : 1;
        return 'ECA-' . $stateCode . '-' . $districtCode . '-' . $year . '-'
             . str_pad($serial, 3, '0', STR_PAD_LEFT);
    }
}