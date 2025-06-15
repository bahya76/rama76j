<?php

use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthProfileController;
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
    // في نفس مجموعة middleware الخاصة بالمستخدم
    Route::get('/health-profile/check-completion', [HealthProfileController::class, 'isProfileComplete']);
   // Route::put('/health-profile', [HealthProfileController::class, 'updateOwn'])->name('health-profile.update-own');
   Route::put('/health-profile-update', [HealthProfileController::class, 'updateOwn'])->name('health-profile.update-own');


});
//Route::put('/health-profile', [HealthProfileController::class, 'updateOwn']);

// روتات المشرف (Admin فقط)
/*Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::put('/health-profile/{userId}', [HealthProfileController::class, 'update'])->name('health-profile.update');
    Route::get('/admin/health-profiles', [HealthProfileController::class, 'index']); // ← مهم لـ pagination
    Route::get('/admin/health-profile/{userId}', [HealthProfileController::class, 'showUserProfile'])->name('admin.health-profile.show');
    Route::get('/health-profile/{id}/progress', [HealthProfileController::class, 'getProgress']);

});*/