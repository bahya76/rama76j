<?php

namespace App\Http\Controllers;

use App\Models\UserHealthProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\UserHealthProfileProgress;
use Illuminate\Support\Facades\Log;


class HealthProfileController extends Controller
{
    // حفظ الملف الصحي للمستخدم
    public function store(Request $request)
    {
        $user = Auth::user(); // ← تحقّق من وجود المستخدم في البداية
        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized']); // ← أرجع خطأ إذا لم يسجل الدخول
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'height' => 'required|numeric|min:0',
            'fitness_level' => 'required|in:beginner,intermediate,advanced',
            'goal' => 'required|in:lose_weight,gain_weight,build_muscle,stay_fit',
            'gender' => 'nullable|in:male,female',
            'age' => 'nullable|integer|min:1',
            'weight' => 'nullable|numeric|min:0',
            'fat_distribution' => 'nullable|in:abdomen,thighs,arms,hips,general',
            'chronic_diseases_or_injuries' => 'nullable|string',
            'waist_circumference' => 'nullable|numeric|min:0',
            'hip_circumference' => 'nullable|numeric|min:0',
            'chest_circumference' => 'nullable|numeric|min:0',
            'arm_circumference' => 'nullable|numeric|min:0',
            'workout_days_per_week' => 'nullable|integer|min:0|max:7',
            'preferred_meals_count' => 'nullable|in:2,3,4,5',
        ]);

        // التحقق إذا كان للمستخدم ملف صحي مسبق
        $existingProfile = UserHealthProfile::where('user_id', $user->id)->first();
        if ($existingProfile) {
            return response()->json([
                'status' => 400,
                'message' => 'User already has a health profile.',
            ]);
        }

        // إنشاء الملف الصحي مع دمج البيانات باستخدام array_merge بدل spread operator  // ← تعديل
        $profile = UserHealthProfile::create(array_merge($validated, [
            'user_id' => $user->id,
            'last_updated_at' => Carbon::now(),
        ]));

        return response()->json([
            'status' => 201,
            'message' => 'Health profile created successfully.',
            'data' => $profile
        ]);
    }

    // عرض الملف الصحي للمستخدم نفسه
    public function show()
    {
        $user = Auth::user(); // ← تحقق من المستخدم
        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized']); // ← حماية إضافية
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

 /* public function updateOwn(Request $request)

{   
  /*  Log::debug('RAW CONTENT:', [$request->getContent()]);
    Log::debug('ALL:', $request->all());

    return response()->json(['data' => $request->all(), 'raw' => $request->getContent()]);///////
    $user = Auth::user();
    if (!$user) {
        return response()->json(['status' => 401, 'message' => 'Unauthorized']);
    }

    $profile = UserHealthProfile::where('user_id', $user->id)->first();
    if (!$profile) {
        return response()->json(['status' => 404, 'message' => 'Health profile not found.']);
    }

    $validated = $request->validate([
        'full_name' => 'sometimes|string|max:255',
        'height' => 'sometimes|numeric|min:0',
        'fitness_level' => 'sometimes|in:beginner,intermediate,advanced',
        'goal' => 'sometimes|in:lose_weight,gain_weight,build_muscle,stay_fit',
        'gender' => 'nullable|in:male,female',
        'age' => 'nullable|integer|min:1',
        'weight' => 'nullable|numeric|min:0',
        'fat_distribution' => 'nullable|in:abdomen,thighs,arms,hips,general',
        'chronic_diseases_or_injuries' => 'nullable|string',
        'waist_circumference' => 'nullable|numeric|min:0',
        'hip_circumference' => 'nullable|numeric|min:0',
        'chest_circumference' => 'nullable|numeric|min:0',
        'arm_circumference' => 'nullable|numeric|min:0',
        'workout_days_per_week' => 'nullable|integer|min:0|max:7',
        'preferred_meals_count' => 'nullable|in:2,3,4,5',
    ]);

    Log::debug('VALIDATED:', $validated);
   // dd($validated);

    $validated['last_updated_at'] = Carbon::now();
    $profile->update($validated);

    // سجل التغييرات إذا أردت
    $changes = [];
    foreach ($validated as $key => $newValue) {
        if ((string) $profile->getOriginal($key) !== (string) $newValue) {
            $changes[$key] = [
                'old' => $profile->getOriginal($key),
                'new' => $newValue
            ];
        }
    }
    if (!empty($changes)) {
        $profile->progressLogs()->create([
            'changed_data' => $changes
        ]);
    }
    $profile->refresh();
    Log::debug('REQUEST DATA:', $request->all());
    return response()->json([
        'status' => 200,
        'message' => 'Health profile updated successfully.',
        'data' => $profile
    ]);
}
   */     
    
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

    // ✅ التحقق من البيانات المرسلة (فقط عند وجودها)
    $validated = $request->validate([
        'full_name' => 'sometimes|string|max:255',
        'height' => 'sometimes|numeric|min:0',
        'fitness_level' => 'sometimes|in:beginner,intermediate,advanced',
        'goal' => 'sometimes|in:lose_weight,gain_weight,build_muscle,stay_fit',
        'gender' => 'nullable|in:male,female',
        'age' => 'nullable|integer|min:1',
        'weight' => 'nullable|numeric|min:0',
        'fat_distribution' => 'nullable|in:abdomen,thighs,arms,hips,general',
        'chronic_diseases_or_injuries' => 'nullable|string|max:500',
        'waist_circumference' => 'nullable|numeric|min:0',
        'hip_circumference' => 'nullable|numeric|min:0',
        'chest_circumference' => 'nullable|numeric|min:0',
        'arm_circumference' => 'nullable|numeric|min:0',
        'workout_days_per_week' => 'nullable|integer|min:0|max:7',
        'preferred_meals_count' => 'nullable|in:2,3,4,5',
    ]);

    // ✅ تحديث تاريخ آخر تعديل
    $validated['last_updated_at'] = Carbon::now();

    // ✅ استخراج القيم القديمة قبل التحديث لمقارنة التغييرات
    $originalData = $profile->getOriginal();

    // ✅ التحديث
    $profile->update($validated);

    // ✅ حفظ التغييرات (اختياري)
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

    // ✅ تحديث النسخة من قاعدة البيانات وإرجاعها
    $profile->refresh();

    return response()->json([
        'status' => 200,
        'message' => 'Health profile updated successfully.',
        'data' => $profile,
    ]);
}
    

    // تعديل الملف الصحي للمستخدم (خاص بالمشرف)
    public function update(Request $request, $userId)
    {
        $admin = Auth::user();

        if (!$admin) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized']); // ← تحقق من تسجيل دخول المشرف
        }

        // التحقق من صلاحيات المشرف
        if (!$admin->is_admin) {
            return response()->json(['status' => 403, 'message' => 'Forbidden']); // ← رسالة أدق لـ 403
        }

        $profile = UserHealthProfile::where('user_id', $userId)->first();

        if (!$profile) {
            return response()->json(['status' => 404, 'message' => 'Health profile not found.']);
        }
        $validated = $request->validate([
            // الحقول الأساسية (إلزامية عند التسجيل الأولي)
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'height' => 'required|numeric|min:0',
            'weight' => 'required|numeric|min:0',
            'fitness_level' => 'required|in:beginner,intermediate,advanced',
            'goal' => 'required|in:lose_weight,gain_weight,build_muscle,stay_fit',
        
            // الحقول الاختيارية (يمكن أن تكون null أو مفقودة)
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

    // عرض ملف صحي لمستخدم معين (خاص بالمشرف)
    public function showUserProfile($userId)
    {
        $admin = Auth::user();

        if (!$admin) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized']); // ← تحقق من تسجيل دخول المشرف
        }

        if (!$admin->is_admin) {
            return response()->json(['status' => 403, 'message' => 'Forbidden']); // ← رسالة أدق لـ 403
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

    // عرض كل الملفات الصحية (خاص بالمشرف)
    public function index()
    {
        $admin = Auth::user();
    
        if (!$admin) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized']);
        }
    
        if (!$admin->is_admin) {
            return response()->json(['status' => 403, 'message' => 'Forbidden']);
        }
    
        // جلب 10 ملفات صحية في كل صفحة
        $profiles = UserHealthProfile::with('user')->paginate(10);
    
        return response()->json([
            'status' => 200,
            'data' => $profiles,
        ]);
    }


    // حذف الملف الصحي (خاص بالمشرف)
    public function destroy($userId)
    {
        $admin = Auth::user();

        if (!$admin) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized']); // ← تحقق من تسجيل دخول المشرف
        }

        if (!$admin->is_admin) {
            return response()->json(['status' => 403, 'message' => 'Forbidden']); // ← رسالة أدق لـ 403
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
    
    // جلب سجل التغييرات مع تحقق الصلاحيات واستخدام Pagination
    public function getProgress($profileId)
    {
        $user = Auth::user();

        $profile = UserHealthProfile::find($profileId);
        if (!$profile) {
            return response()->json(['status' => 404, 'message' => 'Health profile not found.']);
        }

        // تحقق إذا كان صاحب الملف أو مشرف
        if ($profile->user_id !== $user->id && !$user->is_admin) {
            return response()->json(['status' => 403, 'message' => 'Forbidden']);
        }

        // جلب التقدم مع pagination
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

        if (!$user) {
            return response()->json(['status' => 401, 'message' => 'Unauthorized']);
        }

        $profile = UserHealthProfile::where('user_id', $user->id)->first();

        if (!$profile) {
            return response()->json(['status' => 404, 'message' => 'Health profile not found.']);
        }

        return response()->json([
            'status' => 200,
            'is_complete' => $profile->isComplete(),
            'missing_fields' => $profile->missingFields(), // إضافة لمساعدة الواجهة الأمامية في عرض الحقول الناقصة
        ]);
    }
}