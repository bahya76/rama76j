<?php

namespace App\Http\Controllers;
use App\Models\User;

use App\Models\UserHealthProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\UserHealthProfileProgress;
use Illuminate\Support\Facades\Log;


class HealthProfileController extends Controller
{// 🟢 إنشاء ملف صحي جديد للمستخدم الحالي لأول مرة (يُستخدم بعد التسجيل)
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized']);
        }
    
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'height' => 'required|numeric|min:0',
            'fitness_level' => 'required|in:beginner,intermediate,advanced',
            'goal' => 'required|in:lose_weight,gain_weight,build_muscle,stay_fit',
            'gender' => 'sometimes|in:male,female',
            'age' => 'sometimes|integer|min:1',
            'weight' => 'sometimes|numeric|min:0',
            'fat_distribution' => 'sometimes|in:abdomen,thighs,arms,hips,general',
            'chronic_diseases_or_injuries' => 'sometimes|string|max:500',
            'waist_circumference' => 'sometimes|numeric|min:0',
            'hip_circumference' => 'sometimes|numeric|min:0',
            'chest_circumference' => 'sometimes|numeric|min:0',
            'arm_circumference' => 'sometimes|numeric|min:0',
            'workout_days_per_week' => 'sometimes|integer|min:0|max:7',
            'preferred_meals_count' => 'sometimes|in:2,3,4,5',
        ]);
    
        $existingProfile = UserHealthProfile::where('user_id', $user->id)->first();
        if ($existingProfile) {
            return response()->json([
                'status' => 400,
                'message' => 'User already has a health profile.',
            ]);
        }
    
        $profile = UserHealthProfile::create(array_merge($validated, [
            'user_id' => $user->id,
            'last_updated_at' => Carbon::now(),
        ]));
    
        return response()->json([
            'status' => 201,
            'message' => 'Health profile created successfully.',
            'data' => $profile,
        ]);
    }
     // 🟡 عرض الملف الصحي للمستخدم الحالي
    public function show()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized']);
        }
    
        $profile = UserHealthProfile::where('user_id', $user->id)->first();
    
        if (!$profile) {
            return response()->json(['status' => 404, 'message' => 'Health profile not found.']);
        }
    
        return response()->json([
            'status' => 200,
            'data' => $profile,
        ]);
    }

    // 🔵 تعديل الملف الصحي للمستخدم الحالي + تسجيل التغييرات في جدول التقدم
  public function updateOwn(Request $request)
  {
      $user = Auth::user();
  
      if (!$user) {
          return response()->json(['status' => 401, 'message' => 'Unauthorized'], 401);
      }
  
      $profile = UserHealthProfile::where('user_id', $user->id)->first();
  
      if (!$profile) {
          return response()->json(['status' => 404, 'message' => 'Health profile not found.'], 404);
      }
  
      $validated = $request->validate([
          'full_name' => 'sometimes|string|max:255',
          'height' => 'sometimes|numeric|min:0',
          'fitness_level' => 'sometimes|in:beginner,intermediate,advanced',
          'goal' => 'sometimes|in:lose_weight,gain_weight,build_muscle,stay_fit',
          'gender' => 'sometimes|in:male,female',
          'age' => 'sometimes|integer|min:1',
          'weight' => 'sometimes|numeric|min:0',
          'fat_distribution' => 'sometimes|in:abdomen,thighs,arms,hips,general',
          'chronic_diseases_or_injuries' => 'sometimes|string|max:500',
          'waist_circumference' => 'sometimes|numeric|min:0',
          'hip_circumference' => 'sometimes|numeric|min:0',
          'chest_circumference' => 'sometimes|numeric|min:0',
          'arm_circumference' => 'sometimes|numeric|min:0',
          'workout_days_per_week' => 'sometimes|integer|min:0|max:7',
          'preferred_meals_count' => 'sometimes|in:2,3,4,5',
      ]);
  
      $validated['last_updated_at'] = Carbon::now();
  
      $originalData = $profile->getOriginal();
      $profile->update($validated);
  
      $changes = [];
      foreach ($validated as $key => $newValue) {
          $oldValue = $originalData[$key] ?? null;
          if ((string) $oldValue !== (string) $newValue) {
              $changes[$key] = [
                  'old' => $oldValue,
                  'new' => $newValue,
              ];
          }
      }
  
      if (!empty($changes)) {
          $profile->progressLogs()->create([
              'changed_data' => $changes,
          ]);
      }
  
      return response()->json([
          'status' => 200,
          'message' => 'Health profile updated successfully.',
          'data' => $profile->refresh(),
      ]);
  }
    
    // 🔴 تعديل الملف الصحي لأي مستخدم (من قبل مشرف أو مدرب فقط)
public function update(Request $request, $userId)
{
    $auth = Auth::user();

    if (!$auth) {
        return response()->json(['status' => 401, 'message' => 'Unauthorized']);
    }

    // جلب المستخدم المطلوب تحديث ملفه
    $user = User::find($userId);

    if (!$user) {
        return response()->json(['status' => 404, 'message' => 'User not found']);
    }

    // صلاحيات التحقق
    if ($auth->role === 'trainer' && $user->trainer_id !== $auth->id) {
        return response()->json(['status' => 403, 'message' => 'Forbidden']);
    }

    if ($auth->role === 'user') {
        return response()->json(['status' => 403, 'message' => 'Forbidden']);
    }

    // جلب الملف الصحي
    $profile = UserHealthProfile::where('user_id', $userId)->first();

    if (!$profile) {
        return response()->json(['status' => 404, 'message' => 'Health profile not found.']);
    }

    // تحقق وتحديث البيانات
    $validated = $request->validate([
        // الحقول الأساسية
        'full_name' => 'required|string|max:255',
        'gender' => 'required|in:male,female',
        'height' => 'required|numeric|min:0',
        'weight' => 'required|numeric|min:0',
        'fitness_level' => 'required|in:beginner,intermediate,advanced',
        'goal' => 'required|in:lose_weight,gain_weight,build_muscle,stay_fit',

        // الحقول الاختيارية
        'age' => 'nullable|integer|min:1',
        'fat_distribution' => 'nullable|in:abdomen,thighs,arms,hips,general',
        'chronic_diseases_or_injuries' => 'nullable|string|max:500',
        'waist_circumference' => 'nullable|numeric|min:0',
        'hip_circumference' => 'nullable|numeric|min:0',
        'chest_circumference' => 'nullable|numeric|min:0',
        'arm_circumference' => 'nullable|numeric|min:0',
        'workout_days_per_week' => 'nullable|integer|min:0|max:7',
        'preferred_meals_count' => 'nullable|integer|in:2,3,4,5',
    ]);

    // تسجيل التعديلات
    $changes = [];
    foreach ($validated as $key => $newValue) {
        if ($profile->$key != $newValue) {
            $changes[$key] = [
                'old' => $profile->$key,
                'new' => $newValue
            ];
        }
    }

    if (!empty($changes)) {
        $validated['last_updated_at'] = Carbon::now();
        $profile->update($validated);

        $profile->progressLogs()->create([
            'changed_data' => $changes
        ]);
    }

    return response()->json([
        'status' => 200,
        'message' => 'Health profile updated successfully.',
        'data' => $profile
    ]);
}  
    // 🟠 عرض الملف الصحي لمستخدم معين (مخصص للمشرف والمدرب المسؤول فقط)
  public function showUserProfile($userId)
  {
      $auth = Auth::user();

      if (!$auth) {
          return response()->json(['status' => 401, 'message' => 'Unauthorized']);
      }

      $user = User::find($userId);

      if (!$user) {
          return response()->json(['status' => 404, 'message' => 'User not found']);
      }

      // تحقق من صلاحية المدرب
      if ($auth->role === 'trainer' && $user->trainer_id !== $auth->id) {
          return response()->json(['status' => 403, 'message' => 'Forbidden']);
      }

      // super admin له حق الوصول لجميع المستخدمين
      if ($auth->role === 'user') {
          return response()->json(['status' => 403, 'message' => 'Forbidden']);
      }

      $profile = UserHealthProfile::where('user_id', $userId)->first();

      if (!$profile) {
          return response()->json(['status' => 404, 'message' => 'Health profile not found.']);
      }

      return response()->json([
          'status' => 200,
          'data' => $profile,
      ]);
  }

   // ⚪ عرض كل الملفات الصحية (للمشرف أو المدرب) 
    public function index()
  {
      $auth = Auth::user();

      if (!$auth) {
          return response()->json(['status' => 401, 'message' => 'Unauthorized']);
      }

      if ($auth->role === 'user') {
          return response()->json(['status' => 403, 'message' => 'Forbidden']);
      }

      // إذا كان مدربًا، فقط جلب الملفات الصحية لمستخدميه
      if ($auth->role === 'trainer') {
          $profiles = UserHealthProfile::whereHas('user', function ($query) use ($auth) {
              $query->where('trainer_id', $auth->id);
          })->with('user')->paginate(10);
      } else {
          // super admin يرى كل الملفات
          $profiles = UserHealthProfile::with('user')->paginate(10);
      }

      return response()->json([
          'status' => 200,
          'data' => $profiles,
      ]);
  }

  // حذف الملف الصحي (خاص بالمشرف أو super admin)
 public function destroy($userId)
  {
      $auth = Auth::user();

      if (!$auth) {
          return response()->json(['status' => 401, 'message' => 'Unauthorized']);
      }

      $user = User::find($userId);

      if (!$user) {
          return response()->json(['status' => 404, 'message' => 'User not found']);
      }

      if ($auth->role === 'trainer' && $user->trainer_id !== $auth->id) {
          return response()->json(['status' => 403, 'message' => 'Forbidden']);
      }

      if ($auth->role === 'user') {
          return response()->json(['status' => 403, 'message' => 'Forbidden']);
      }

      $profile = UserHealthProfile::where('user_id', $userId)->first();

      if (!$profile) {
          return response()->json(['status' => 404, 'message' => 'Health profile not found.']);
      }

      $profile->delete();

      return response()->json([
          'status' => 200,
          'message' => 'Health profile deleted successfully.',
      ]);
  }

    // 🟣 جلب سجل التعديلات للملف الصحي (Progress Logs) مع صلاحيات وتحكم بالوصول
    public function getProgress($profileId)
  {
      $auth = Auth::user();

      if (!$auth) {
          return response()->json(['status' => 401, 'message' => 'Unauthorized']);
      }

      $profile = UserHealthProfile::find($profileId);

      if (!$profile) {
          return response()->json(['status' => 404, 'message' => 'Health profile not found.']);
      }

      $profileOwner = $profile->user;

      // التحقق من الصلاحية
      if (
          $auth->role === 'user' && $profile->user_id !== $auth->id ||
          $auth->role === 'trainer' && $profileOwner->trainer_id !== $auth->id
      ) {
          return response()->json(['status' => 403, 'message' => 'Forbidden']);
      }

      $progress = UserHealthProfileProgress::where('user_health_profile_id', $profileId)
          ->orderBy('created_at', 'desc')
          ->paginate(15);

      return response()->json([
          'status' => 200,
          'progress' => $progress,
      ]);
  }

  // التحقق من اكتمال الملف الصحي للمستخدم الحالي
  public function isProfileComplete()
  {
      $user = Auth::user();

      if (!$user || $user->role !== 'user') {
          return response()->json(['status' => 401, 'message' => 'Unauthorized']);
      }

      $profile = UserHealthProfile::where('user_id', $user->id)->first();

      if (!$profile) {
          return response()->json(['status' => 404, 'message' => 'Health profile not found.']);
      }

      return response()->json([
          'status' => 200,
          'is_complete' => $profile->isComplete(),
          'missing_fields' => $profile->missingFields(),
      ]);
  }
}