<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use constGuards;
use constDefaults;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class AdminController extends Controller
{
    public function loginHandler(Request $request) 
    {
        $fieldType = filter_var($request->login_id, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if ($fieldType == 'email') {
            $request->validate([
                'login_id' => 'required|email|exists:admins,email',
                'password' => 'required|min:5|max:45'
            ], [
                'login_id.required' => 'Email or username is required',
                'login_id.email' => 'Invalid email address',
                'login_id.exists' => 'Email is not exists',
                'password.required' => 'Password is required'
            ]);
        } else {
            $request->validate([
                'login_id' => 'required|exists:admins,username',
                'password' => 'required|min:5|max:45'
            ], [
                'login_id.required' => 'Email or username is required',
                'login_id.exists' => 'Username is not exists',
                'password.required' => 'Password is required'
            ]);
        }

        $credentials = array(
            $fieldType => $request->login_id,
            'password' => $request->password
        );

        if (Auth::guard('admin')->attempt($credentials)) {
            return redirect()->route('admin.home');
        } else {
            session()->flash('errMsg', 'Incorrect credentials');
            return redirect()->route('admin.login');
        }
    }

    public function logoutHandler(Request $request) 
    {
        Auth::guard('admin')->logout();
        session()->flash('errMsg', 'You are logged out');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    }

    public function sendPasswordResetLink(Request $request) 
    {
        $request->validate([
            'email' => 'required|email|exists:admins,email'
        ], [
            'email.required' => 'The :attribute is required',
            'email.email' => 'Invalid email address',
            'email.exists' => 'The :attribute is not exists in system'
        ]);

        $admin = Admin::where('email', $request->email)->first();

        $token = base64_encode(Str::random(64));

        $oldToken = DB::table('password_reset_tokens')
                        ->where(['email' => $request->email, 'guard' => constGuards::ADMIN])
                        ->first();
        
        if ($oldToken) {
            DB::table('password_reset_tokens')
                ->where(['email' => $request->email, 'guard' => constGuards::ADMIN])
                ->update([
                    'token' => $token,
                    'created_at' => Carbon::now()
                ]);
        } else {
            DB::table('password_reset_tokens')->insert([
                'email'=> $request->email,
                'guard' => constGuards::ADMIN,
                'token' => $token,
                'created_at' => Carbon::now()
            ]);
        }

        $actionLink = route('admin.reset-password', [
            'token' => $token,
            'email' => $request->email
        ]);

        $data = array(
            'actionLink' => $actionLink,
            'admin' => $admin
        );

        $mail_body = view('emails.admin-forgot-email', $data)->render();

        $mailConfig = array(
            'mail_from_email' => env('EMAIL_FROM_ADDRESS'),
            'mail_from_name' => env('EMAIL_FROM_NAME'),
            'mail_recipient_email' => $admin->email,
            'mail_recipient_name' => $admin->name,
            'mail_subject' => 'Reset password',
            'mail_body' => $mail_body
        );

        if (sendEmail($mailConfig)) {
            session()->flash('success', 'We have emailed your password reset link');
            return redirect()->route('admin.forgot-password');
        } else {
            session()->flash('errMsg', 'Something went wrong!');
            return redirect()->route('admin.forgot-password');
        }
    }

    public function resetPassword($token = null) 
    {
        $check_token = DB::table('password_reset_tokens')   
            ->where(['token' => $token, 'guard' => constGuards::ADMIN])
            ->first();
        
        if ($check_token) {
            $diffMins = Carbon::createFromFormat('Y-m-d H:i:s', $check_token->created_at)
                ->diffInMinutes(Carbon::now());
            
            if ($diffMins > constDefaults::tokenExpiredMinutes) {
                session()->flash('errMsg', 'Invalid token, request another reset password link');
                return redirect()->route('admin.forgot-password', ['token' => $token]);
            } else {
                return view('back.pages.admin.auth.reset-password', ['token' => $token]);
            }
        } else {
            session()->flash('errMsg', 'Invalid token, request another reset password link');
            return redirect()->route('admin.forgot-password');
        }
    }   

    public function resetPasswordHandler(Request $request) {
        $request->validate([
            'new_password' => 'required|min:5|max:45|
                               required_with:new_password_confirmation|same:new_password_confirmation',
            'new_password_confirmation' => 'required'
        ]);

        $token = DB::table('password_reset_tokens')
                    ->where(['token'=> $request->token, 'guard' => constGuards::ADMIN])
                    ->first();

        $admin = Admin::where('email', $token->email)->first();

        Admin::where('email', $admin->email)->update([
            'password' => Hash::make($request->new_password)
        ]);

        DB::table('password_reset_tokens')->where([
            'token'=> $token->token,
            'email' => $admin->email,
            'guard' => constGuards::ADMIN
        ])->delete();

        $mail_body = view('emails.admin-reset-email', [
            'admin' => $admin,
            'new_password' => $request->new_password
        ])->render();

        $mailConfig = array(
            'mail_from_email' => env('EMAIL_FROM_ADDRESS'),
            'mail_from_name' => env('EMAIL_FROM_NAME'),
            'mail_recipient_email' => $admin->email,
            'mail_recipient_name' => $admin->name,
            'mail_subject' => 'Password change',
            'mail_body' => $mail_body
        );

        sendEmail($mailConfig);
        return redirect()->route('admin.login')->with('success', 'Your password have been changed');
    }
}
