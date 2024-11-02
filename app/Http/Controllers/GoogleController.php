<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Facades\JWTAuth;

class GoogleController extends Controller
{
    const DRIVER_TYPE = 'google';

    public function handleGoogleRedirect()
    {
        return Socialite::driver(static::DRIVER_TYPE)
            ->redirect();
    }

    public function handleGoogleCallback()
    {
        try {
            $user = Socialite::driver(static::DRIVER_TYPE)->user();
        } catch (Exception $e) {
            return redirect('/login')->withErrors(['msg' => 'Unable to authenticate with Google']);
        }

        try {
            $userExisted = User::where('email', $user->email)->first();

            if ($userExisted) {
                $userExisted->update([
                    'oauth_id' => $user->id,
                    'oauth_type' => static::DRIVER_TYPE,
                ]);
                Auth::login($userExisted, true);
            } else {
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => 'user' . uniqid(),
                    'oauth_id' => $user->id,
                    'oauth_type' => static::DRIVER_TYPE,
                    'password' => Hash::make($user->id),
                ]);
                Auth::login($newUser, true);
            }

            // Generate JWT token for the user
            $token = JWTAuth::fromUser(Auth::user());

            // Return token to frontend
            return response()->json(['token' => $token]);
        } catch (Exception $e) {
            return redirect('/login')->withErrors(['msg' => 'Unable to create or find user']);
        }
    }
    public function handleGoogleCallback_old()
    {
        try {
            $user = Socialite::driver(static::DRIVER_TYPE)->user();
        } catch (Exception $e) {
            return redirect('/login')->withErrors(['msg' => 'Unable to authenticate with Facebook']);
        }

        try {
            $userExisted = User::where('email', $user->email)->first();

            if ($userExisted) {
                $userExisted->update([
                    'oauth_id' => $user->id,
                    'oauth_type' => static::DRIVER_TYPE,
                ]);

                Auth::login($userExisted, true);

                return redirect()->route('home');
            } else {
                $newUser = User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => 'user' . uniqid(),
                    'oauth_id' => $user->id,
                    'oauth_type' => static::DRIVER_TYPE,
                    'password' => Hash::make($user->id),
                ]);

                Auth::login($newUser, true);
                return redirect()->route('home');
            }
        } catch (Exception $e) {
            return redirect('/login')->withErrors(['msg' => 'Unable to create or find user']);
        }
    }

    <?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class GoogleController extends Controller
{
    // Redirect to Google's OAuth 2.0 server (only if using browser-based login flow)
    public function handleGoogleRedirect()
    {
        // You can redirect to Google's OAuth 2.0 server here if using the redirection method
    }

    // Handle the callback from Google (only if using browser-based login flow)
    public function handleGoogleCallback()
    {
        // You can handle the callback from Google here if using the redirection method
    }

    // Login using the Google ID token sent from React frontend
    public function loginWithGoogleToken(Request $request)
    {
        // Initialize Google client with your credentials
        $client = new Google_Client(['client_id' => env('GOOGLE_CLIENT_ID')]);

        // Verify the ID token
        $payload = $client->verifyIdToken($request->id_token);

        if ($payload) {
            // The token is valid, proceed with login or user registration
            $googleId = $payload['sub'];  // Google user ID
            $email = $payload['email'];
            $name = $payload['name'];

            // Check if user already exists
            $user = User::where('email', $email)->orWhere('google_id', $googleId)->first();

            if (!$user) {
                // If the user does not exist, create a new one
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'password' => Hash::make(uniqid()), // Generate a random password, as it's not needed for OAuth
                ]);
            }

            // Log the user in
            Auth::login($user);

            // Generate a JWT or session token (if using JWT)
            $token = $user->createToken('auth_token')->plainTextToken;

            // Return the user and token to the frontend
            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
        } else {
            return response()->json(['message' => 'Invalid Google token'], 401);
        }
    }
}

}
