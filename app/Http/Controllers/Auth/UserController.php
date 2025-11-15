<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function create()
    {
        // Opsional: kalau mau hanya admin/owner yang boleh
        // if (! auth()->user()->is_admin) {
        //     abort(403, 'Tidak punya akses.');
        // }

        return view('users.create');
    }

    public function store(Request $request)
    {
        // Opsional: proteksi admin saja
        // if (! auth()->user()->is_admin) {
        //     abort(403, 'Tidak punya akses.');
        // }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'is_admin' => ['nullable', 'boolean'], // kalau pakai flag admin
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_admin' => isset($data['is_admin']) ? (bool) $data['is_admin'] : false,
        ]);

        return redirect()
            ->route('users.create')
            ->with('success', 'User baru berhasil dibuat: ' . $user->email);
    }
}
