<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class MonthlyFeeDue extends Model
{
    protected $fillable = ['member_profile_id', 'amount', 'due_date', 'status', 'paid_date', 'month_year'];
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'due_date' => 'date', 'paid_date' => 'date'];
    }
    public function memberProfile()
    {
        return $this->belongsTo(MemberProfile::class);
    }
}