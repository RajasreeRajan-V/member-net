<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundraisingCampaign extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'description',
        'image',
        'target_amount',
        'raised_amount',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'raised_amount' => 'decimal:2',
            'end_date'      => 'date',
        ];
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function donations()
    {
        return $this->hasMany(FundraisingDonation::class, 'campaign_id');
    }

    // ── AUTO STATUS RULE ──
    // Runs on every save(), no matter which controller/job touches the model.
    protected static function booted()
    {
        static::saving(function (FundraisingCampaign $campaign) {
            // Never touch a manually cancelled campaign
            if ($campaign->status === 'CANCELLED') {
                return;
            }

            if ((float) $campaign->raised_amount >= (float) $campaign->target_amount) {
                $campaign->status = 'COMPLETED';
            } else {
                // Covers: admin raises the target above what's already raised,
                // or a donation gets refunded and drops raised_amount back down.
                $campaign->status = 'ACTIVE';
            }
        });
    }
}