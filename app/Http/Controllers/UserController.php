<?php

namespace App\Http\Controllers;
use Symfony\Component\Console\Output\ConsoleOutput;

use Illuminate\Http\Request;
use App\Models\User as User;
use Illuminate\Support\Facades\Validator;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\UserHealthProfile;
class UserController extends Controller
{
    public function login(Request $R)
    {
        // البحث عن المستخدم بالبريد الإلكتروني
        $user = User::where('email', $R->email)->first();
    
        // إذا لم يُعثر على المستخدم
        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'No account found with this email'
            ]);
        }
    
        // التحقق من صحة كلمة المرور
        if (!Hash::check($R->password, $user->password)) {
            return response()->json([
                'status' => 401,
                'message' => 'Wrong password'
            ]);
        }
    
        // التحقق مما إذا كان الحساب مفعل (تم التحقق منه عبر الإيميل)
        if (!$user->is_approved) {
            return response()->json([
                'status' => 403,
                'message' => 'Account not verified. Please check your email to activate your account.',
                'is_approved' => false
            ]);
        }
    
        // تسجيل الدخول ناجح: توليد التوكن والرد بالمعلومات
        $token = $user->createToken('Personal Access Token')->plainTextToken;
      // التحقق من وجود ملف صحي للمستخدم
       $hasHealthProfile = $user->healthProfile()->exists();
          // تحديد رابط إعادة التوجيه بناءً على وجود ملف صحي
    $redirectTo = $hasHealthProfile 
          ? route('exercises.index')          // رابط صفحة التمارين (مثال)
         : route('health-profile.store');    // رابط صفحة إنشاء الملف الصحي (مثال)
        return response()->json([
            'status' => 200,
            'message' => 'Successfully Login! Welcome Back',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'role' => $user->role,
                'is_approved' => $user->is_approved,
            ],
            'is_approved' => true,
            'has_health_profile' => $hasHealthProfile ,// <-- مفيد للواجهة الأمامية
            'redirect_to' => $redirectTo
        ]);
    }
    

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'password_confirmation' => 'required',
            'phone_number'=> 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $data = $request->only(['name', 'email', 'password','phone_number']);
        $data['password'] = Hash::make($data['password']);
        $data['verify_code'] = rand(1000, 99999);
        $data['is_approved'] = false;
        $data['role'] = 'user';


        $user = User::create($data);
    

     /*  Mail::raw("Your verification code is: {$user->verify_code}", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Verification Code');
        });*/
        $email = $request->input('email');
        $code = rand(100000, 999999); // أو أي طريقة لإنشاء الكود

        if (app()->environment('local')) {
            // فقط سجّل الكود بدل إرساله
            Log::info("Verification code: $code");
        } else {
            Mail::raw("Your verification code is: $code", function ($message) use ($email) {
                $message->to($email)->subject('Verification Code');
            });
        }

        return response()->json([
            'status' => 201,
            'user' => $user,
            'token' => $user->createToken('register-token')->plainTextToken,
            'message' => 'Registration successful. Please check your email for verification.',
        ]);
    }

    // تحقق من كود التفعيل
    public function verifyCode(Request $R)
    {
        $user = User::where('email', $R->email)
                    ->where('verify_code', $R->code)
                    ->first();

        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'User not found']);
        }

        $user->is_approved = true;
        $user->verify_code = null;
        $user->save();

        return response()->json(['status' => 200, 'message' => 'Account verified successfully']);
    }

    // إرسال كود تحقق جديد
    public function sendVerifyCode(Request $R)
    {
        $user = User::where('email', $R->email)->first();

        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'User not found']);
        }

        $user->verify_code = rand(1000, 99999);
        $user->save();

        Mail::raw("Your new verification code is: {$user->verify_code}", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('New Verification Code');
        });

        return response()->json(['status' => 200, 'message' => 'New verification code sent to your email']);
    }

    public function show(User $user)
    {
        return response()->json($user);
    }
    public function sendPasswordResetCode(Request $request)
    {
        // التحقق من وجود المستخدم باستخدام البريد الإلكتروني
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'User not found']);
        }
    
        // توليد كود تحقق جديد
        $code = rand(100000, 999999);
        $user->verify_code = $code;
        $user->save();
    
        // إرسال الكود إلى بريد المستخدم
        Mail::raw("Your password reset code is: {$code}", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Password Reset Code');
        });
    
        return response()->json(['status' => 200, 'message' => 'Password reset code sent to your email']);
    }
    
    public function resetPassword(Request $request)
    {
        // التحقق من وجود المستخدم باستخدام البريد الإلكتروني
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json(['status' => 404, 'message' => 'User not found']);
        }
    
        // التحقق من أن الكود المدخل يطابق كود التحقق المخزن
        if ($user->verify_code !== $request->code) {
            return response()->json(['status' => 400, 'message' => 'Invalid verification code']);
        }
    
        // التحقق من صحة كلمة المرور الجديدة
        $validator = Validator::make($request->all(), [
            'password' => 'required|min:6|confirmed',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
    
        // تحديث كلمة المرور
        $user->password = Hash::make($request->password);
        $user->verify_code = null; // مسح كود التحقق بعد التغيير
        $user->save();
    
        return response()->json(['status' => 200, 'message' => 'Password reset successfully']);
    }
    /* public function showOrders(User $user)
    {
        return response()->json($user->orders()->with(['product'])->get());
    }*/
}