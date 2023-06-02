<?php

namespace App\Http\Controllers;

use App\Mail\GenerateMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        if (Auth::attempt(Arr::only($request->all(), ['email', 'password']), $request->remember)) {
            $user = Auth::user();
            $user['token'] = $user->createToken(str_replace(
                ' ',
                '-',
                env('APP_NAME')
            ) . '_user')->accessToken;
            return response()->json($user);
        }
        return response()->json([
            'message' => 'Invalid credentials!'
        ], 403);
    }

    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->email_verified_at) {
            return response()->json(['message' => 'Email is already verified.'], 409);
        }
        Mail::to($request->user()->email)->send(new GenerateMail(
            'emails.verify_email',
            'Please verify your email',
            ['token' => Crypt::encryptString($request->user()->email)]
        ));
        return response()->json([
            'message' => 'Verification email sent.'
        ]);
    }

    public function verifyEmail(Request $request)
    {
        if ($request->user()->email == Crypt::decryptString($request->token)) {
            if (!$request->user()->email_verified_at) {
                $request->user()->update([
                    'email_verified_at' => now()
                ]);
            }
            return response()->json([
                'message' => 'Email verified, welcome ... '
            ]);
        } else {
            return response()->json([
                'message' => 'Invalid token!'
            ], 403);
        }
    }

    public function logout()
    {
        Auth::user()->tokens()->delete();
        return response()->json([
            'message' => 'Successfully logged out',
        ]);
    }
}
