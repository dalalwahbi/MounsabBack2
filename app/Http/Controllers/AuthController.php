<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Client;
use App\Models\Prestataire;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Google\Client as GoogleClient;

class AuthController extends Controller
{
    public function addUser(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|string|in:admin,client,prestataire',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 401);
        }

        $user = User::create([
            'firstName' => $request->firstName,
            'lastName' => $request->lastName,
            'phone' => $request->phone,
            'role' => $request->role,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        switch ($request->role) {
            case 'admin':
                Admin::create(['user_id' => $user->id]);
                break;
            case 'client':
                Client::create(['user_id' => $user->id]);
                break;
            case 'prestataire':
                Prestataire::create(['user_id' => $user->id]);
                break;
        }

        return response()->json(compact('user'), 201);
    }


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        $user = JWTAuth::user();

        $role = $user->role;

        return response()->json([
            'token' => $token,
            'user' => Auth::user(),
            'role' => $role,
        ]);
    }

    public function logout()
    {
        Auth::logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function me()
    {
        return response()->json(Auth::user());
    }

    public function verifyToken(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            return response()->json(['valid' => true, 'user' => $user, 'message' => null], 200);
        } catch (\Exception $e) {
            return response()->json(['valid' => false, 'message' => 'Votre session a expiré. Veuillez vous reconnecter.'], 401);
        }
    }

    public function googleLogin(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'required|string',
            'google_id' => 'required|string',
        ]);

        try {
            // Verify if the user exists
            $user = User::where('email', $validated['email'])->first();

            if ($user) {
                if ($request->google_id) {
                    $user->google_id = $request->google_id;
                    $user->save();

                    $token = JWTAuth::fromUser($user);

                    return response()->json([
                        'success' => true,
                        'message' => 'Utilisateur trouvé',
                        'user' => $user,
                        'token' => $token,
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "L'utilisateur n'est pas inscrit via Google",
                ], 400);
            }
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => "Erreur lors de la création du token",
            ], 500);
        } catch (\Exception $err) {
            return response()->json([
                'success' => false,
                'message' => "Erreur interne",
                'error' => $err->getMessage(),
            ], 500);
        }
    }
}
