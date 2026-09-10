<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;

/**
 * @property \App\Models\Wallet|null $wallet
 */
#[Fillable([
    'registro_universitario',
    'name',
    'alias',
    'email',
    'password',
    'avatar',
    'team_id'
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

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
    public function team()
    {
        return $this->belongsTo(Team::class);
    }
    public function getAdminlteImageAttribute()
    {
        return $this->avatar ?? asset('vendor/adminlte/dist/assets/img/AdminLTELogo.png');
    }
    public function adminlte_image()
    {
        return $this->avatar ?? asset('vendor/adminlte/dist/assets/img/AdminLTELogo.png');
    }
    protected static function booted()
    {
        static::created(function ($user) {
            $user->wallet()->create([
                'saldo_actual' => 0.00
            ]);
        });
    }

    // Y defines su relación:
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
}
