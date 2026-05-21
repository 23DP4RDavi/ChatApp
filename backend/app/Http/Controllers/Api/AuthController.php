<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    private function hasUserColumn(string $column): bool
    {
        try {
            return Schema::hasTable('users') && Schema::hasColumn('users', $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function googleRedirect()
    {
        try {
            return Socialite::driver('google')->stateless()->redirect();
        } catch (\Throwable $e) {
            return redirect($this->frontendAuthUrl([
                'oauth_error' => 'google_config_invalid',
            ]));
        }
    }

    public function googleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            $email = trim((string) $googleUser->getEmail());

            if ($email === '') {
                return redirect($this->frontendAuthUrl([
                    'oauth_error' => 'google_email_missing',
                ]));
            }

            $googleId = (string) $googleUser->getId();
            $displayName = trim((string) ($googleUser->getName() ?: $googleUser->getNickname() ?: Str::before($email, '@')));

            $hasGoogleId = Schema::hasColumn('users', 'google_id');
            $hasAuthProvider = Schema::hasColumn('users', 'auth_provider');
            $query = User::query();

            if ($hasGoogleId) {
                $query->where('google_id', $googleId)
                    ->orWhere('email', $email);
            } else {
                $query->where('email', $email);
            }

            $user = $query->first();

            if (!$user) {
                $payload = [
                    'name' => $displayName !== '' ? $displayName : 'Google User',
                    'email' => $email,
                    'password' => Hash::make(Str::random(40)),
                ];

                if ($hasGoogleId) {
                    $payload['google_id'] = $googleId;
                }

                if ($hasAuthProvider) {
                    $payload['auth_provider'] = 'google';
                }

                $user = User::create($payload);
            } else {
                if ($hasGoogleId && !$user->google_id) {
                    $user->google_id = $googleId;
                }

                if ($hasAuthProvider && !$user->auth_provider) {
                    $user->auth_provider = 'google';
                }

                if (empty($user->name) && $displayName !== '') {
                    $user->name = $displayName;
                }

                $user->save();
            }

            $this->syncBootstrapAdmin($user);
            $user->touch();
            $token = $user->createToken('auth_token')->plainTextToken;

            return redirect($this->frontendAuthUrl([
                'token' => $token,
                'provider' => 'google',
            ]));
        } catch (\Throwable $e) {
            return redirect($this->frontendAuthUrl([
                'oauth_error' => 'google_callback_failed',
            ]));
        }
    }

    public function register(Request $request)
    {
        $hasUsername = $this->hasUserColumn('username');

        $rules = [
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ];

        if ($hasUsername) {
            $rules['username'] = 'required|string|max:50|min:3|unique:users|regex:/^[a-zA-Z0-9_]+$/';
        }

        $validated = $request->validate($rules);

        $payload = [
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'password' => Hash::make($validated['password']),
        ];

        if ($hasUsername) {
            $payload['username'] = trim($validated['username']);
        }

        $user = User::create($payload);

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
        $userQuery = User::where('email', $validated['login']);
        if ($this->hasUserColumn('username')) {
            $userQuery->orWhere('username', $validated['login']);
        }

        $user = $userQuery->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        $this->syncBootstrapAdmin($user);
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
        $user = $request->user();
        $this->syncBootstrapAdmin($user);
        $user->touch();

        return response()->json([
            'user' => $user,
        ]);
    }

    public function updateUser(Request $request)
    {
        $user = $request->user();

        $hasUsername = $this->hasUserColumn('username');
        $hasAvatarDrawingData = $this->hasUserColumn('avatar_drawing_data');
        $hasAvatarThumbnail = $this->hasUserColumn('avatar_thumbnail');

        $rules = [
            'name' => 'required|string|max:255|min:2',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:8|confirmed',
        ];

        if ($hasUsername) {
            $rules['username'] = [
                'required',
                'string',
                'max:50',
                'min:3',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ];
        }

        if ($hasAvatarDrawingData) {
            $rules['avatar_drawing_data'] = 'nullable|string|max:1000000';
        }

        if ($hasAvatarThumbnail) {
            $rules['avatar_thumbnail'] = 'nullable|string|max:1000000';
        }

        $validated = $request->validate($rules);

        if (!empty($validated['password'])) {
            if (empty($validated['current_password']) || !Hash::check($validated['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => ['The current password is incorrect.'],
                ]);
            }

            $user->password = Hash::make($validated['password']);
        }

        $user->name = trim($validated['name']);
        if ($hasUsername && array_key_exists('username', $validated)) {
            $user->username = trim($validated['username']);
        }
        $user->email = trim($validated['email']);
        if ($hasAvatarDrawingData && array_key_exists('avatar_drawing_data', $validated)) {
            $user->avatar_drawing_data = $validated['avatar_drawing_data'];
        }
        if ($hasAvatarThumbnail && array_key_exists('avatar_thumbnail', $validated)) {
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

    private function syncBootstrapAdmin(User $user): void
    {
        $bootstrapEmail = strtolower(trim((string) env('ADMIN_BOOTSTRAP_EMAIL', '')));

        if ($bootstrapEmail === '') {
            return;
        }

        if (strtolower((string) $user->email) !== $bootstrapEmail) {
            return;
        }

        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'is_admin')) {
            return;
        }

        if ($user->is_admin) {
            return;
        }

        try {
            $user->is_admin = true;
            $user->save();
        } catch (\Throwable $e) {
            // Never block auth/user endpoints because of bootstrap-admin sync.
        }
    }
}
