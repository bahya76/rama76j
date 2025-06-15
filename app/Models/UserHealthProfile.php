<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserHealthProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'height',
        'fitness_level',
        'goal',
        'gender',
        'age',
        'weight',
        'fat_distribution',
        'chronic_diseases_or_injuries',
        'waist_circumference',
        'hip_circumference',
        'chest_circumference',
        'arm_circumference',
        'workout_days_per_week',
        'preferred_meals_count',
        'last_updated_at',
    ];

    protected $casts = [
        'last_updated_at' => 'datetime',
    ];

    protected $dates = [
        'last_updated_at',
    ];
    public function progressLogs()
    {
        return $this->hasMany(UserHealthProfileProgress::class, 'user_health_profile_id');
    }
    // UserHealthProfile.php

public function isComplete()
{
    return !is_null($this->weight) &&
           !is_null($this->age) &&
           !is_null($this->fat_distribution) &&
           !is_null($this->chronic_diseases_or_injuries) &&
           !is_null($this->waist_circumference) &&
           !is_null($this->hip_circumference) &&
           !is_null($this->chest_circumference) &&
           !is_null($this->arm_circumference) &&
           !is_null($this->workout_days_per_week) &&
           !is_null($this->preferred_meals_count);
}

    // دالة إظهار الحقول الناقصة
    public function missingFields()
    {
        $missing = [];
        $fields = [
            'weight', 'age', 'fat_distribution', 'chronic_diseases_or_injuries',
            'waist_circumference', 'hip_circumference', 'chest_circumference',
            'arm_circumference', 'workout_days_per_week', 'preferred_meals_count'
        ];
        foreach ($fields as $field) {
            if (is_null($this->$field)) $missing[] = $field;
        }
        return $missing;
    }

    /**
     * Get the user that owns the health profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
