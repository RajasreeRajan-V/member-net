<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;
    use HasApiTokens;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'auth_token',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'auth_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password'  => 'hashed',
            'is_active' => 'boolean',
            //              ↑ returns true/false
            //                instead of 1/0
        ];
    }

    // ─────────────────────────────────
    // GENERATE AUTH TOKEN
    // Only for member role
    // ─────────────────────────────────
    public function generateAuthToken(): string
    {
        $token = Str::random(60);

        // Only set for member role
        if ($this->role === 'member') {
            $this->auth_token = $token;
            $this->save();
        }

        return $token;
    }

    // ─────────────────────────────────
    // CLEAR AUTH TOKEN
    // ─────────────────────────────────
    public function clearAuthToken(): void
    {
        if ($this->role === 'member') {
            $this->auth_token = null;
            $this->save();
        }
    }
    
    // Check if user is super admin
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    // Check if user is unit admin
    public function isUnitAdmin(): bool
    {
        return $this->role === 'unit_admin';
    }

    // Check if user is district admin
public function isDistrictAdmin(): bool
{
    return $this->role === 'district_admin';
}

    // Check if user is member
    public function isMember(): bool
    {
        return $this->role === 'member';
    }

// User has one UnitAdmin profile
public function unitAdmin()
{
    return $this->hasOne(UnitAdmin::class, 'user_id');
}

// ── ADD THIS ──
// User has one DistrictAdmin profile
public function districtAdmin()
{
    return $this->hasOne(DistrictAdmin::class, 'user_id');
}

// User has one MemberProfile
public function memberProfile()
{
    return $this->hasOne(MemberProfile::class, 'user_id');
}

}
