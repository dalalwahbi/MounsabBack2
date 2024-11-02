<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver('google')->user();
        } catch (Exception $e) {
            return response()->json(['error' => 'Unable to authenticate with Google'], 401);
        }

        $existingUser = User::where('email', $user->email)->first();

        if ($existingUser) {
            Auth::login($existingUser);
        } else {
            $newUser = User::create([
                'name' => $user->name,
                'email' => $user->email,
                'password' => Hash::make(uniqid()), // Use a generated password
            ]);
            Auth::login($newUser);
        }

        // Generate a token (if using Sanctum)
        $token = Auth::user()->createToken('API Token')->plainTextToken;

        return response()->json([
            'user' => Auth::user(),
            'token' => $token,
        ]);
    }
}
