@extends('layouts.app')

@section('title', 'Login')

@push('head')
    <style>
        .auth-wrap {
            max-width: 420px;
            margin-inline: auto;
            margin-top: 60px;
        }

        .card-auth {
            background: var(--card);
            border-radius: 16px;
            border: 1px solid var(--line);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.25);
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }
    </style>
@endpush

@section('content')
    <div class="auth-wrap">
        <div class="card card-auth">
            <div class="card-body p-4 p-md-5">
                <div class="mb-4 text-center">
                    <div class="h5 mb-1">Masuk ke ERP</div>
                    <div class="help">
                        Login dengan kode karyawan & password.
                    </div>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger small">
                        <div class="fw-semibold mb-1">Login gagal:</div>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('login.submit') }}" method="POST" class="mt-3">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label small">Kode Karyawan</label>
                        <input type="text" name="employee_code"
                            class="form-control form-control-sm @error('employee_code') is-invalid @enderror"
                            value="{{ old('employee_code') }}" autofocus>
                        <div class="help">
                            Contoh: <span class="mono">MRF</span>, <span class="mono">BBI</span>, <span
                                class="mono">OWN</span>.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Password</label>
                        <input type="password" name="password"
                            class="form-control form-control-sm @error('password') is-invalid @enderror">
                        <div class="help">
                            Default semua user baru: <span class="mono">123</span> (bisa kamu ganti nanti).
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check small">
                            <input type="checkbox" name="remember" id="remember" class="form-check-input"
                                {{ old('remember') ? 'checked' : '' }}>
                            <label for="remember" class="form-check-label">
                                Ingat saya
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Masuk
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
