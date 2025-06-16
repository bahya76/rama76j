<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
class Trainer extends Model
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'registration_code',
        'super_admin_id',
    ];

    protected $hidden = [
        'password',
        //'remember_token',
    ];

    public function superAdmin()
    {
        return $this->belongsTo(SuperAdmin::class);
    }
    public function users()
{
    return $this->hasMany(User::class); // يفترض أن موديل المستخدمين اسمه User
}

}
