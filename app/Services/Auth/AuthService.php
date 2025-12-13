<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;

class AuthService
{
    /**
     * Handle user login.
     *
     * @param array $credentials
     * @param bool $remember
     * @return bool
     * @throws ValidationException
     */
    public function login(array $credentials, bool $remember = false): bool
    {
        // Check if user exists first
        $user = User::where('email', $credentials['email'])->first();
        
        if (!$user) {
            throw ValidationException::withMessages([
                'email' => 'Email tidak ditemukan dalam sistem.',
            ]);
        }

        if (Auth::attempt($credentials, $remember)) {
            request()->session()->regenerate();
            
            // Update last login (optional)
            $user->update(['last_login_at' => now()]);
            
            return true;
        }

        throw ValidationException::withMessages([
            'email' => 'Email atau password yang Anda masukkan salah.',
        ]);
    }

    /**
     * Handle user logout.
     *
     * @return void
     */
    public function logout(): void
    {
        // Log out the user
        Auth::logout();

        // Invalidate the session
        request()->session()->invalidate();

        // Regenerate the CSRF token
        request()->session()->regenerateToken();
    }
}