<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\EmailVerification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class UserController extends Controller
{
    public function loadRegister()
    {
        return view('register');
    }

    public function studentRegister(Request $request)
    {
        $request->validate([
            'name' => 'string|required|min:2',
            'email' => 'string|email|required|max:100|unique:users',
            'password' => 'string|required|confirmed|min:6'
        ]);

        $user = new User;
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->save();

        return redirect("/verification/" . $user->id);
    }

    public function loadLogin()
    {
        if (Auth::user()) {
            return redirect('/dashboard');
        }
        return view('login');
    }

    public function sendOtp($user)
    {
        $otp = rand(100000, 999999);
        $time = Carbon::now(); // Correct datetime format

        EmailVerification::updateOrCreate(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'otp' => $otp,
                'created_at' => $time
            ]
        );

        $data['email'] = $user->email;
        $data['title'] = 'Mail Verification';
        $data['body'] = 'Your OTP is: ' . $otp;

        Mail::send('mailVerification', ['data' => $data], function ($message) use ($data) {
            $message->to($data['email'])->subject($data['title']);
        });
    }

    public function userLogin(Request $request)
    {
        $request->validate([
            'email' => 'string|required|email',
            'password' => 'string|required'
        ]);

        $userCredential = $request->only('email', 'password');
        $userData = User::where('email', $request->email)->first();

        if ($userData && $userData->is_verified == 0) {
            $this->sendOtp($userData);
            return redirect("/verification/" . $userData->id);
        } elseif (Auth::attempt($userCredential)) {
            return redirect('/dashboard');
        } else {
            return back()->with('error', 'Username & Password is incorrect');
        }
    }

    public function loadDashboard()
    {
        if (Auth::user()) {
            return view('dashboard');
        }
        return redirect('/');
    }

    public function verification($id)
    {
        $user = User::find($id);
        if (!$user || $user->is_verified == 1) {
            return redirect('/');
        }
        $email = $user->email;
        $this->sendOtp($user); // Send OTP

        return view('verification', compact('email'));
    }

    public function verifiedOtp(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        $otpData = EmailVerification::where('email', $request->email)->where('otp', $request->otp)->first();

        if (!$otpData) {
            return response()->json(['success' => false, 'msg' => 'You entered the wrong OTP']);
        }

        $currentTime = Carbon::now();
        $otpTime = new Carbon($otpData->created_at);

        if ($currentTime->diffInSeconds($otpTime) <= 90) {
            User::where('id', $user->id)->update(['is_verified' => 1]);
            return response()->json(['success' => true, 'msg' => 'Mail has been verified']);
        } else {
            return response()->json(['success' => false, 'msg' => 'Your OTP has expired']);
        }
    }

    public function resendOtp(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        $otpData = EmailVerification::where('email', $request->email)->first();

        $currentTime = Carbon::now();
        $otpTime = new Carbon($otpData->created_at);

        if ($currentTime->diffInSeconds($otpTime) <= 90) {
            return response()->json(['success' => false, 'msg' => 'Please try after some time']);
        } else {
            $this->sendOtp($user);
            return response()->json(['success' => true, 'msg' => 'OTP has been sent']);
        }
    }
}