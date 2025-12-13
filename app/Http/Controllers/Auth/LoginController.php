<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Auth\AuthService;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /**
     * The AuthService instance.
     */
    protected AuthService $authService;

    /**
     * Create a new controller instance.
     */
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Show the login form.
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle the login request.
     * Fixed method name to match route
     */
    public function submitLogin(LoginRequest $request)
    {
        // // Rate limiting untuk mencegah brute force
        // $key = 'login.' . $request->ip();

        // if (cache()->has($key) && cache()->get($key) >= 20) {
        //     return back()->withErrors([
        //         'email' => 'Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.'
        //     ])->withInput();
        // }

        // Get validated data
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        try {
            $this->authService->login($credentials, $remember);

            // // Clear login attempts on success
            // cache()->forget($key);

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Login berhasil. Selamat datang!');

        } catch (\Exception $e) {
            // Increment login attempts
            // $attempts = cache()->get($key, 0) + 1;
            // cache()->put($key, $attempts, now()->addMinutes(15));

            return back()
                ->withErrors(['email' => $e->getMessage()])
                ->withInput($request->except('password'));
        }
    }

    /**
     * Handle the logout request.
     */
    public function logout(Request $request)
    {
        try {
            $this->authService->logout();

            return redirect()->route('login')
                ->with('success', 'Anda telah berhasil logout.');

        } catch (\Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Terjadi kesalahan saat logout.');
        }
    }
}
