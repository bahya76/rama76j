<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'role', // بدل is_admin
        'verify_code',
        'is_approved',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'verify_code', // يمكن إخفاؤه إن أردتِ عدم إظهاره للـ frontend

    ]; 
    public function isSuperAdmin()
    {
        return $this->role === 'super_admin';
    }

    public function isTrainer()
    {
        return $this->role === 'trainer';
    }

    public function isUser()
    {
        return $this->role === 'user';
    }

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_admin' => 'boolean',
        'is_approved' => 'boolean',
    ];
    public function healthProfile()
{
    return $this->hasOne(UserHealthProfile::class);
}
public function trainer()
{
    return $this->belongsTo(Trainer::class);
}

}
