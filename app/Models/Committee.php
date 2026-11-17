<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Committee extends Model
{
    protected $fillable = ['name', 'description', 'start_date', 'end_date', 'created_by', 'is_active'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    // Admin-selected members with a role each (Member, Coordinator, etc.)
    public function members()
    {
        return $this->belongsToMany(MemberProfile::class, 'committee_members', 'committee_id', 'member_profile_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    // Who created this committee (super admin, district admin or unit admin)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}