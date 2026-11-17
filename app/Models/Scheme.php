<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Scheme extends Model
{
    protected $fillable = ['title', 'description', 'amount', 'duration_months', 'start_date', 'end_date', 'created_by', 'is_active'];
    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date'];
    }
    public function dues()
    {
        return $this->hasMany(SchemeFeeDue::class);
    }
    // Admin-selected members who should see/pay this scheme
    public function members()
    {
        return $this->belongsToMany(MemberProfile::class, 'scheme_members', 'scheme_id', 'member_profile_id');
    }
    public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by');
}
}