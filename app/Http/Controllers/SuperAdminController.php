<?php

namespace App\Http\Controllers;
use App\Models\SuperAdmin;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class SuperAdminController extends Controller
{
   
    // تسجيل دخول الأدمن الأساسي
    public function login(Request $request)
    {
        $admin = SuperAdmin::where('email', $request->email)->first();

        if (!$admin || !Hash::check($request->password, $admin->password)) {
            return response()->json(['status' => 401, 'message' => 'Invalid credentials']);
        }

        $token = $admin->createToken('superadmin-token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'message' => 'Login successful',
            'token' => $token,
            'admin' => [
                'id' => $admin->id,
                'name' => $admin->name,
                'email' => $admin->email,
            ]
        ]);
    }

    // إنشاء مدرب جديد مع كود خاص
    public function registerTrainer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:50',
            'email'    => 'required|email|unique:trainers',
            'password' => 'required|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 422, 'errors' => $validator->errors()]);
        }

        $trainerCode = rand(100000, 999999); // كود خاص بالمدرب

        $trainer = Trainer::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'code'      => $trainerCode,
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 201,
            'message' => 'Trainer registered successfully',
            'trainer' => $trainer
        ]);
    }

    // حذف مدرب
    public function deleteTrainer($id)
    {
        $trainer = Trainer::find($id);

        if (!$trainer) {
            return response()->json(['status' => 404, 'message' => 'Trainer not found']);
        }

        $trainer->delete();

        return response()->json(['status' => 200, 'message' => 'Trainer deleted']);
    }

    // حذف مستخدم عادي
    public function deleteUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'User not found']);
        }

        $user->delete();

        return response()->json(['status' => 200, 'message' => 'User deleted']);
    }

    // حظر مستخدم
    public function blockUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'User not found']);
        }

        $user->is_blocked = true;
        $user->save();

        return response()->json(['status' => 200, 'message' => 'User has been blocked']);
    }

    // فك الحظر عن مستخدم
    public function unblockUser($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'User not found']);
        }

        $user->is_blocked = false;
        $user->save();

        return response()->json(['status' => 200, 'message' => 'User has been unblocked']);
    }
}
