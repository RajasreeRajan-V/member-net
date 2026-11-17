<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DistrictAdmin extends Model
{
    protected $fillable = [
        'user_id',
        'district_id',
        'assigned_by',
    ];

    // ─────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────

    // Belongs to User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Belongs to District
    public function district()
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    // Assigned by Super Admin
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}