<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OtherFee extends Model
{
    protected $fillable = ['title', 'amount', 'due_date', 'created_by', 'is_active'];
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'due_date' => 'date'];
    }
    public function payments()
    {
        return $this->hasMany(OtherFeePayment::class);
    }
    public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by');
}
}