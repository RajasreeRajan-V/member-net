<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'unit_id',
        'district_id',
        'state_id',
        'membership_type',
        'membership_id',
        'status',
        'dob',
        'photo',
        'organization',
        'address', 
        'is_fumma_member', 
        'joined_date',
        'expiry_date',
    ];

    protected $attributes = [
        'membership_type' => 'ANNUAL',
        'membership_id'   => null,
        'is_fumma_member' => 0, 
    ];
   
   protected $casts = [
    'is_fumma_member' => 'boolean', 
    'gallery_last_seen_at' => 'datetime',// NEW
];
    // ─────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}