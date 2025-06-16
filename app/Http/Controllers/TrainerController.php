<?php

namespace App\Http\Controllers;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class TrainerController extends Controller
{
   
    // تسجيل دخول المدرب
    public function login(Request $request)
    {
        $trainer = Trainer::where('email', $request->email)->first();

        if (!$trainer) {
            return response()->json(['status' => 404, 'message' => 'Trainer not found']);
        }

        if (!Hash::check($request->password, $trainer->password)) {
            return response()->json(['status' => 401, 'message' => 'Incorrect password']);
        }

        // إنشاء توكن (مثلاً باستخدام Sanctum)
        $token = $trainer->createToken('trainer-token')->plainTextToken;

        return response()->json([
            'status' => 200,
            'message' => 'Login successful',
            'token' => $token,
            'trainer' => $trainer,
        ]);
    }

    // عرض المستخدمين (المتدربين) التابعين لهذا المدرب
    public function users(Request $request)
    {
        $trainer = $request->user(); // يجب التأكد من أنك تستخدم middleware للتحقق من توكن المدرب

        $users = $trainer->users()->get();

        return response()->json(['status' => 200, 'users' => $users]);
    }

    // إضافة مستخدم جديد تحت هذا المدرب
    public function addUser(Request $request)
    {
        $trainer = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
            // يمكن إضافة حقول أخرى حسب الحاجة
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 400, 'errors' => $validator->errors()]);
        }

        $data = $request->only(['name', 'email', 'password']);
        $data['password'] = Hash::make($data['password']);
        $data['trainer_id'] = $trainer->id; // ربط المستخدم بالمدرب

        $user = User::create($data);

        return response()->json(['status' => 201, 'message' => 'User added successfully', 'user' => $user]);
    }

    // حذف مستخدم تابع لهذا المدرب
    public function deleteUser(Request $request, $userId)
    {
        $trainer = $request->user();

        $user = User::where('id', $userId)->where('trainer_id', $trainer->id)->first();

        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'User not found or not your trainee']);
        }

        $user->delete();

        return response()->json(['status' => 200, 'message' => 'User deleted successfully']);
    }
}
