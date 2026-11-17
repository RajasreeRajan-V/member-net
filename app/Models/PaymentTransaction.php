<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    protected $fillable = [
        'user_id', 'type', 'reference_id', 'title', 'amount',
        'method', 'gateway_transaction_id', 'status', 'paid_date',
    ];
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_date' => 'date'];
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}