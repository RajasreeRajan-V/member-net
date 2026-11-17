<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean'
        ];
    }

    // State has many districts
    public function districts()
    {
        return $this->hasMany(District::class);
    }

    // State has many Units through Districts
    public function units()
    {
        return $this->hasManyThrough(
            Unit::class,
            District::class,
            'state_id',   // FK on districts table
            'district_id' // FK on units table
        );
    }

    // ── NEW: returns the locked state (Kerala), or null if multi-state mode ──
    public static function fixed(): ?self
    {
        $name = config('membership.fixed_state');

        if (!$name) {
            return null; // setting is empty = normal multi-state behaviour
        }

        return static::firstOrCreate(
            ['name' => $name],
            ['is_active' => true]
        );
    }
}