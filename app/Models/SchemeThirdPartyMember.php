<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchemeThirdPartyMember extends Model
{
    protected $fillable = [
        'unit_id',
        'name',
        'email',
        'phone',
        'dob',
        'organization',
        'address',
        'photo',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
        ];
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function schemes()
    {
        return $this->belongsToMany(Scheme::class, 'scheme_third_party_member', 'third_party_member_id', 'scheme_id');
    }

    public function dues()
    {
        return $this->hasMany(SchemeFeeDue::class, 'third_party_member_id');
    }
}
