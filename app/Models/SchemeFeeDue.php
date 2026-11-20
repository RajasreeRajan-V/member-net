<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SchemeFeeDue extends Model
{
    protected $fillable = [
        'scheme_id',
        'member_profile_id',
        'third_party_member_id',
        'amount',
        'due_date',
        'status',
        'paid_date',
        'month_year',
        'payment_method',
        'notes',
    ];

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

    public function thirdPartyMember()
    {
        return $this->belongsTo(SchemeThirdPartyMember::class, 'third_party_member_id');
    }

    public function getIsThirdPartyAttribute(): bool
    {
        return !empty($this->third_party_member_id);
    }

    public function getPayerNameAttribute(): string
    {
        if ($this->third_party_member_id && $this->thirdPartyMember) {
            return $this->thirdPartyMember->name;
        }
        return $this->memberProfile?->user?->name ?? '—';
    }

    public function getPayerEmailAttribute(): ?string
    {
        if ($this->third_party_member_id && $this->thirdPartyMember) {
            return $this->thirdPartyMember->email;
        }
        return $this->memberProfile?->user?->email;
    }

    public function getPayerPhoneAttribute(): ?string
    {
        if ($this->third_party_member_id && $this->thirdPartyMember) {
            return $this->thirdPartyMember->phone;
        }
        return $this->memberProfile?->user?->phone;
    }

    public function getPayerUnitNameAttribute(): string
    {
        if ($this->third_party_member_id && $this->thirdPartyMember) {
            return $this->thirdPartyMember->unit->name ?? '—';
        }
        return $this->memberProfile?->unit?->name ?? '—';
    }
}