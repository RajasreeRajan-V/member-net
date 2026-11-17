<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'member_profile_id',
        'event_registration_id', // ← ADD
        'user_id',
        'plan_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_type',
        'transaction_id',
        'membership_id',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount'  => 'decimal:2',
    ];

    // ── RELATIONSHIPS ──
    public function memberProfile()
    {
        return $this->belongsTo(
            MemberProfile::class, 'member_profile_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function plan()
    {
        return $this->belongsTo(
            MembershipPlan::class, 'plan_id'
        );
    }
    // ── ADD RELATIONSHIP ──
public function eventRegistration()
{
    return $this->belongsTo(
        EventRegistration::class,
        'event_registration_id'
    );
}
}