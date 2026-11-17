<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventRegistration extends Model
{
    protected $fillable = [
        'event_id',
        'user_id',
        'member_profile_id',
        'ticket_number',
        'payment_status',
        'payment_amount',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'payment_amount' => 'decimal:2',
        ];
    }

    // ─────────────────────────────────
    // RELATIONSHIPS
    // ─────────────────────────────────
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function memberProfile()
    {
        return $this->belongsTo(
            MemberProfile::class, 'member_profile_id'
        );
    }

    // ─────────────────────────────────
    // GENERATE TICKET NUMBER
    // ECA-EVT-2025-001
    // ─────────────────────────────────
    public static function generateTicketNumber($eventId)
    {
        $year  = date('Y');
        $count = self::where('event_id', $eventId)->count() + 1;
        $serial = str_pad($count, 3, '0', STR_PAD_LEFT);
        return 'ECA-EVT-' . $year . '-' . $eventId
               . '-' . $serial;
    }
}