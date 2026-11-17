<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    protected $fillable = ['state_id', 'name', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean'
        ];
    }

    // District belongs to state
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    // District has many units
    public function units()
    {
        return $this->hasMany(Unit::class);
    }
    public function memberProfiles()
{
    return $this->hasMany(\App\Models\MemberProfile::class);
}
public function districtAdmin()
{
    return $this->hasOne(DistrictAdmin::class);
}
}
