<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = [
        'district_id',
        'name',
        'unit_id',
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

    public function getFormattedIdAttribute(): string
    {
        return $this->unit_id ?? str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    public static function generateUnitId(string $name, ?int $ignoreId = null): string
    {
        $letters = strtoupper(preg_replace('/[^A-Za-z]/', '', $name));
        $prefix  = substr($letters . 'XXX', 0, 3);

        $existing = static::where('unit_id', 'LIKE', $prefix . '%')
            ->when($ignoreId, fn($q) => $q->where('id', '!=', $ignoreId))
            ->pluck('unit_id');

        $maxSerial = 0;
        foreach ($existing as $uid) {
            $serial = (int) substr($uid, 3);
            if ($serial > $maxSerial) {
                $maxSerial = $serial;
            }
        }

        return $prefix . str_pad($maxSerial + 1, 3, '0', STR_PAD_LEFT);
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
