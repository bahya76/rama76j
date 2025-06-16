<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthProfileController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\TrainerController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
// روتات تسجيل الدخول والتسجيل والتحقق بدون مصادقة
Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);
Route::post('/verify-code', [UserController::class, 'verifyCode']);
Route::post('/send-verify-code', [UserController::class, 'sendVerifyCode']);
Route::post('send-password-reset-code', [UserController::class, 'sendPasswordResetCode']);
Route::post('reset-password', [UserController::class, 'resetPassword']);







// روتات المستخدم العادي (تسجيل الدخول ضروري)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/health-profile', [HealthProfileController::class, 'show'])->name('health-profile.show');
    Route::post('/health-profile', [HealthProfileController::class, 'store'])->name('health-profile.store');
    Route::get('/health-profile/check-completion', [HealthProfileController::class, 'isProfileComplete']);
    Route::put('/health-profile-update', [HealthProfileController::class, 'updateOwn'])->name('health-profile.update-own');
});

// روتات المشرف (Admin فقط)
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::put('/health-profile/{userId}', [HealthProfileController::class, 'update'])->name('health-profile.update');
    Route::get('/admin/health-profiles', [HealthProfileController::class, 'index']); // ← مهم لـ pagination
    Route::get('/admin/health-profile/{userId}', [HealthProfileController::class, 'showUserProfile'])->name('admin.health-profile.show');
    Route::get('/health-profile/{id}/progress', [HealthProfileController::class, 'getProgress']);

});


////خاص بالادمن الاساسي
Route::prefix('super-admin')->middleware(['auth:sanctum', 'superadmin'])->group(function () {
    // تسجيل دخول الأدمن الأساسي (يمكن تكون بدون كود تحقق)
    Route::post('/login', [SuperAdminController::class, 'login']);

    // إنشاء مدرب (أدمن ثانوي) مع إعطاء كود تسجيل خاص
    Route::post('/trainers', [SuperAdminController::class, 'registerTrainer']);

    // حذف مدرب
    Route::delete('/trainers/{id}', [SuperAdminController::class, 'deleteTrainer']);

    // حذف مستخدم عادي (متدرب)
    Route::delete('/users/{id}', [SuperAdminController::class, 'deleteUser']);

    // حظر/إلغاء حظر مستخدم
    Route::put('/users/{id}/block', [SuperAdminController::class, 'blockUser']);
    Route::put('/users/{id}/unblock', [SuperAdminController::class, 'unblockUser']);

    // عرض جميع المدربين
    Route::get('/trainers', [SuperAdminController::class, 'listTrainers']);

    // عرض جميع المستخدمين
    Route::get('/users', [SuperAdminController::class, 'listUsers']);
});
///خاص بالمدربين 

Route::prefix('trainer')->middleware(['auth:sanctum', 'trainer'])->group(function () {
    // تسجيل دخول المدرب
    Route::post('/login', [TrainerController::class, 'login']);

    // عرض المتدربين المرتبطين بهذا المدرب
    Route::get('/trainees', [TrainerController::class, 'listTrainees']);

    // إضافة متدرب مرتبط بالمدرب
    Route::post('/trainees', [TrainerController::class, 'addTrainee']);

    // حذف متدرب
    Route::delete('/trainees/{id}', [TrainerController::class, 'deleteTrainee']);

    // تحديث بيانات متدرب
    Route::put('/trainees/{id}', [TrainerController::class, 'updateTrainee']);
});

