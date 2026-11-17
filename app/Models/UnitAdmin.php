<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitAdmin extends Model
{
    protected $fillable = [
        'user_id',
        'unit_id',
        'assigned_by'
    ];

    // ─────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────

    // Belongs to User
    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    // Belongs to Unit
    public function unit()
    {
        return $this->belongsTo(
            Unit::class,
            'unit_id'
        );
    }

    // Assigned by Super Admin
    public function assignedBy()
    {
        return $this->belongsTo(
            User::class,
            'assigned_by'
        );
    }
}