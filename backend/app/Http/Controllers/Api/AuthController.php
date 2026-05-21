<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function googleRedirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function googleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (\Throwable $e) {
            return redirect($this->frontendAuthUrl([
                'oauth_error' => 'google_login_failed',
            ]));
        }

        $email = trim((string) $googleUser->getEmail());

        if ($email === '') {
            return redirect($this->frontendAuthUrl([
                'oauth_error' => 'google_email_missing',
            ]));
        }

        $googleId = (string) $googleUser->getId();
        $displayName = trim((string) ($googleUser->getName() ?: $googleUser->getNickname() ?: Str::before($email, '@')));

        $user = User::where('google_id', $googleId)
            ->orWhere('email', $email)
            ->first();

        if (!$user) {
            $user = User::create([
                'name' => $displayName !== '' ? $displayName : 'Google User',
                'username' => $this->generateUniqueUsername($displayName !== '' ? $displayName : Str::before($email, '@')),
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
                'google_id' => $googleId,
                'auth_provider' => 'google',
            ]);
        } else {
            if (!$user->google_id) {
                $user->google_id = $googleId;
            }

            if (!$user->auth_provider) {
                $user->auth_provider = 'google';
            }

            if (empty($user->username)) {
                $user->username = $this->generateUniqueUsername($displayName !== '' ? $displayName : Str::before($email, '@'));
            }

            if (empty($user->name) && $displayName !== '') {
                $user->name = $displayName;
            }

            $user->save();
        }

        $user->touch();
        $token = $user->createToken('auth_token')->plainTextToken;

        return redirect($this->frontendAuthUrl([
            'token' => $token,
            'provider' => 'google',
        ]));
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|min:2',
            'username' => 'required|string|max:50|min:3|unique:users|regex:/^[a-zA-Z0-9_]+$/',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => trim($validated['name']),
            'username' => trim($validated['username']),
            'email' => trim($validated['email']),
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        // Try to find user by email or username
        $user = User::where('email', $validated['login'])
            ->orWhere('username', $validated['login'])
            ->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user->touch();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    public function user(Request $request)
    {
        $request->user()->touch();

        return response()->json([
            'user' => $request->user(),
        ]);
    }

    public function updateUser(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255|min:2',
            'username' => [
                'required',
                'string',
                'max:50',
                'min:3',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
            'avatar_drawing_data' => 'nullable|string|max:1000000',
            'avatar_thumbnail' => 'nullable|string|max:1000000',
        ]);

        if (!empty($validated['password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password is incorrect.'],
                ]);
            }

            $user->password = Hash::make($validated['password']);
        }

        $user->name = trim($validated['name']);
        $user->username = trim($validated['username']);
        $user->email = trim($validated['email']);
        if (array_key_exists('avatar_drawing_data', $validated)) {
            $user->avatar_drawing_data = $validated['avatar_drawing_data'];
        }
        if (array_key_exists('avatar_thumbnail', $validated)) {
            $user->avatar_thumbnail = $validated['avatar_thumbnail'];
        }
        $user->touch();
        $user->save();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    private function frontendAuthUrl(array $query = []): string
    {
        $base = rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');
        $url = $base . '/auth';

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    private function generateUniqueUsername(string $raw): string
    {
        $base = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '_', trim($raw)) ?: 'user');
        $base = trim($base, '_');

        if ($base === '') {
            $base = 'user';
        }

        if (strlen($base) < 3) {
            $base = 'user_' . $base;
        }

        $base = substr($base, 0, 45);
        $candidate = $base;
        $counter = 1;

        while (User::where('username', $candidate)->exists()) {
            $suffix = (string) $counter;
            $candidate = substr($base, 0, max(1, 45 - strlen($suffix))) . $suffix;
            $counter++;
        }

        return $candidate;
    }
}
