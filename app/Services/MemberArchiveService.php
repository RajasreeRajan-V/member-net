<?php

namespace App\Services;

use App\Models\DeletedMember;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MemberArchiveService
{
    /**
     * Copy the member into deleted_members, then permanently remove the
     * profile + user rows so the email / phone can be reused.
     */
    public function archiveAndDelete(MemberProfile $profile, ?int $deletedBy = null, string $via = 'admin'): void
    {
        DB::transaction(function () use ($profile, $deletedBy, $via) {
            // withTrashed: the user may already be soft-deleted (old data)
            $user = User::withTrashed()->find($profile->user_id);

            DeletedMember::create([
                'original_user_id'    => $profile->user_id,
                'original_profile_id' => $profile->id,
                'name'                => $user?->name ?? 'Unknown',
                'email'               => $user?->email,
                'phone'               => $user?->phone,
                'membership_id'       => $profile->membership_id,
                'membership_type'     => $profile->membership_type,
                'status'              => $profile->status,
                'dob'                 => $profile->dob,
                'organization'        => $profile->organization,
                'address'             => $profile->address,
                'photo'               => $profile->photo, // file is kept on disk
                'is_fumma_member'     => (bool) $profile->is_fumma_member,
                'unit_id'             => $profile->unit_id,
                'district_id'         => $profile->district_id,
                'state_id'            => $profile->state_id,
                'joined_date'         => $profile->joined_date,
                'expiry_date'         => $profile->expiry_date,
                'registered_at'       => $profile->created_at,
                'deleted_by'          => $deletedBy,
                'deleted_via'         => $via,
                'deleted_at'          => now(),
            ]);

            if ($user) {
                $user->tokens()->delete();
            }

            $profile->forceDelete();
            $user?->forceDelete();
        });
    }
}