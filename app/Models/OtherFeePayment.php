<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class OtherFeePayment extends Model
{
    protected $fillable = ['other_fee_id', 'member_profile_id', 'status', 'paid_date'];
    protected function casts(): array
    {
        return ['paid_date' => 'date'];
    }
    public function otherFee()
    {
        return $this->belongsTo(OtherFee::class);
    }
    public function memberProfile()
    {
        return $this->belongsTo(MemberProfile::class);
    }
}