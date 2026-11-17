<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SchemeFeeDue extends Model
{
    protected $fillable = ['scheme_id', 'member_profile_id', 'amount', 'due_date', 'status', 'paid_date', 'month_year'];
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'due_date' => 'date', 'paid_date' => 'date'];
    }
    public function scheme()
    {
        return $this->belongsTo(Scheme::class);
    }
    public function memberProfile()
    {
        return $this->belongsTo(MemberProfile::class);
    }
}