<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        // Kalau sudah login, jangan boleh buka login lagi
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'employee_code' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'employee_code.required' => 'Kode karyawan wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $remember = $request->boolean('remember', false);

        // login pakai employee_code
        $credentials = [
            'employee_code' => $data['employee_code'],
            'password' => $data['password'],
        ];

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()
            ->withErrors([
                'employee_code' => 'Kode karyawan atau password salah.',
            ])
            ->withInput($request->only('employee_code'));
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
