<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedMember extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'original_user_id', 'original_profile_id',
        'name', 'email', 'phone',
        'membership_id', 'membership_type', 'status',
        'dob', 'organization', 'address', 'photo', 'is_fumma_member',
        'unit_id', 'district_id', 'state_id',
        'joined_date', 'expiry_date', 'registered_at',
        'deleted_by', 'deleted_via', 'deleted_at',
    ];

    protected $casts = [
        'is_fumma_member' => 'boolean',
        'deleted_at'      => 'datetime',
        'registered_at'   => 'datetime',
    ];
}