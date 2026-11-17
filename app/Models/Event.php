<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'description',
        'image',
        'event_date',
        'event_time',
        'location',
        'ticket_type',      // nullable — kept for legacy, always FREE now
        'ticket_price',     // nullable — always 0 now
        'total_seats',
        'registered_count',
        'registration_deadline',
        'status',
        // is_online, online_link removed from fillable (no longer used)
    ];

    protected $casts = [
        'event_date'            => 'date',
        'registration_deadline' => 'date',
        'ticket_price'          => 'decimal:2',
        // is_online cast removed
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function registrations()
    {
        return $this->hasMany(EventRegistration::class, 'event_id');
    }

    public function hasSeatsAvailable(): bool
    {
        if ($this->total_seats == 0) return true;
        return $this->registered_count < $this->total_seats;
    }

    public function remainingSeats(): string|int
    {
        if ($this->total_seats == 0) return 'Unlimited';
        return $this->total_seats - $this->registered_count;
    }
}