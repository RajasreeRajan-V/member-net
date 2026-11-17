<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Renewal extends Model
{
    protected $fillable = [
        'member_profile_id',
        'title',
        'type',
        'valid_from',
        'valid_until',
        'renewal_date',
        'notes',
        'created_by',
    ];

    // 'status' is a computed accessor, not a DB column — always included in JSON output
    protected $appends = ['status'];

    protected function casts(): array
    {
        return [
            'valid_from'   => 'date',
            'valid_until'  => 'date',
            'renewal_date' => 'date',
        ];
    }

    // ── Auto-fill renewal_date if not explicitly provided ──
    protected static function booted()
    {
        static::saving(function ($renewal) {
            if (!$renewal->renewal_date && $renewal->valid_until) {
                $renewal->renewal_date = Carbon::parse($renewal->valid_until)->addDay();
            }
        });
    }

    public function memberProfile()
    {
        return $this->belongsTo(MemberProfile::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Only two states: ACTIVE (valid today or later) / EXPIRED (past valid_until)
    public function getStatusAttribute(): string
    {
        if (!$this->valid_until) {
            return 'ACTIVE';
        }

        return Carbon::parse($this->valid_until)->startOfDay()->lt(Carbon::today())
            ? 'EXPIRED'
            : 'ACTIVE';
    }
}