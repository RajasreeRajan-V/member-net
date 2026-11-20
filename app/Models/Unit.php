<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'district_id',
        'name',
        'is_active',
        'certificate_logo',
        'certificate_seal_text',
        'certificate_president_name',
        'certificate_secretary_name',
        'signatory_1_name',
        'signatory_1_title',
        'signatory_1_signature',
        'signatory_2_name',
        'signatory_2_title',
        'signatory_2_signature',
        'signatory_3_name',
        'signatory_3_title',
        'signatory_3_signature',
    ];

    public function getCertificateLogoUrlAttribute()
    {
        if ($this->certificate_logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->certificate_logo)) {
            return asset('storage/' . $this->certificate_logo);
        }
        return asset('fumma-logo.png');
    }

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
