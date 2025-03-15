<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Attribute;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Account extends Authenticatable
{
    use HasApiTokens, CanResetPassword, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'owner_id');
    }

    public function access_tenants()
    {
        return $this->belongsToMany(Tenant::class, 'tenant_access')
            ->withPivot('ability');
    }

    public function getAccessableAttribute(): \Illuminate\Support\Collection
    {
        $id = array_merge(
            $this->access_tenants->pluck('id')->toArray(),
            $this->tenants->pluck('id')->toArray(),
        );

        return Tenant::whereIn('id', $id)->get()->map(function ($tenant) {
            if ($this->tenants->where('id', $tenant->id)->first()) {
                $tenant->ability = ['OWNER'];
            }
            else if ($access = $this->access->where('id', $tenant->id)->first()) {
                $tenant->ability = json_decode($access->pivot->ability);
            }
            else $tenant->ability = [];

            return [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'ability' => $tenant->ability,
            ];
        });
    }

    public function scopeFindUsername($query, $username)
    {
        return $query->where('phone', $username)->orWhere('email', $username)->first();
    }

    public function sendPasswordResetNotification($token) {
        $url = env('APP_URL') .'/account/reset-password?token='.$token;
        logger('password reset url: ' . $url);
        // return $this->notify(new ResetPasswordNotification($url));
    }

    public function createTokenApp (\Illuminate\Http\Request $request)
    {
        $deviceName = $request->userAgent() ?? $request->getUserInfo() ?? 'unknown';
        $expired = now()->addDays($request->get('remember') ? 360 : 7);
        $ability = ['membership'];

        return $this->createToken($deviceName, $ability, $expired);
    }
}
