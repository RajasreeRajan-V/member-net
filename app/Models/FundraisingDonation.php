<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FundraisingDonation extends Model
{
    protected $fillable = [
    'campaign_id',
    'user_id',
    'amount',
    'status',
    'payment_method',
    'gateway_transaction_id',
];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function campaign()
    {
        return $this->belongsTo(FundraisingCampaign::class, 'campaign_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}