<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = ['district_id', 'name', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean'
        ];
    }

    // Unit belongs to district
    public function district()
    {
        return $this->belongsTo(District::class);
    }

    // Unit has one Admin
public function unitAdmin()
{
    return $this->hasOne(UnitAdmin::class, 'unit_id');
}
public function memberProfiles()
{
    return $this->hasMany(\App\Models\MemberProfile::class);
}
public function members()
{
    return $this->hasMany(MemberProfile::class, 'unit_id');
}
}
