<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserHealthProfileProgress extends Model
{
    use HasFactory;

    protected $table = 'user_health_profile_progress';

    protected $fillable = [
        'user_health_profile_id',
        'changed_data',
    ];

    protected $casts = [
        'changed_data' => 'array', // تحويل JSON إلى مصفوفة تلقائيًا
    ];

    // العلاقة مع UserHealthProfile
    public function healthProfile()
    {
        return $this->belongsTo(UserHealthProfile::class, 'user_health_profile_id');
    }
}
