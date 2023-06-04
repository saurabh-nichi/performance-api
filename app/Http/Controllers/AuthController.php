<?php

namespace App\Http\Controllers;

use App\Mail\GenerateMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

use function App\Helpers\translate;

class AuthController extends Controller
{
    /**
     * Register a new user
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);
        return response()->json([
            'message' => translate('messages.user_created'),
            'user' => $user
        ]);
    }

    /**
     * Login a user
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
            'message' => translate('messages.errors.invalid_credentials')
        ], 403);
    }

    /**
     * Send user email verification mail
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendVerificationEmail(Request $request)
    {
        if ($request->user()->email_verified_at) {
            return response()->json(['message' => translate('messages.errors.email_already_verified')], 409);
        }
        Mail::to($request->user()->email)->send(new GenerateMail(
            'emails.verify_email',
            translate('messages.email.subjects.verify_email'),
            ['token' => Crypt::encryptString($request->user()->email)]
        ));
        return response()->json([
            'message' => translate('messages.verification_email_sent')
        ]);
    }

    /**
     * Verify user email from token
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyEmail(Request $request)
    {
        if ($request->user()->email == Crypt::decryptString($request->token)) {
            if (!$request->user()->email_verified_at) {
                $request->user()->update([
                    'email_verified_at' => now()
                ]);
            }
            return response()->json([
                'message' => translate('messages.email_verified')
            ]);
        } else {
            return response()->json([
                'message' => translate('messages.errors.invalid_token')
            ], 403);
        }
    }

    /**
     * Log out user
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::user()->tokens()->delete();
        return response()->json([
            'message' => translate('messages.logged_out'),
        ]);
    }

    /**
     * Generate/verify password reset token & update user password
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateResetPasswordToken(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if (!$user->email_verified_at) {
            return response()->json([
                'message' => translate('messages.errors.verify_email_first')
            ], 403);
        }
        if ($request->token) {
            $request->validate(['email' => 'exists:password_reset_tokens']);
            $record = DB::table('password_reset_tokens')->select('token')
                ->where('email', $request->email)->first();
            if (!Hash::check($request->token, $record->token)) {
                return response()->json([
                    'message' => translate('messages.errors.invalid_token')
                ], 403);
            }
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'message' => translate('messages.errors.old_new_password_same')
                ], 403);
            }
            $user->password = $request->new_password;
            $user->save();
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            Mail::to($request->email)->send(new GenerateMail(
                'emails.password_reset',
                translate('messages.email.subjects.password_reset')
            ));
            return response()->json([
                'message' => translate('messages.password_reset')
            ]);
        }
        $token = Str::random(8);
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => bcrypt($token),
            'created_at' => now()
        ]);
        Mail::to($request->email)->send(new GenerateMail(
            'emails.reset_password',
            translate('messages.email.subjects.reset_password'),
            ['token' => $token]
        ));
        return response()->json([
            'message' => translate('messages.token_generated')
        ]);
    }
}
