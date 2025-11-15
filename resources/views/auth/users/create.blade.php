@extends('layouts.app')

@section('title', 'User • Tambah User')

@push('head')
    <style>
        .page-wrap {
            max-width: 640px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        .required::after {
            content: '*';
            color: #ef4444;
            margin-left: 3px;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Tambah User Baru</h4>
                <div class="text-muted small">
                    Owner / admin bisa membuat akun login baru untuk tim.
                </div>
            </div>
            <div>
                <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger small">
                <div class="fw-semibold mb-1">Terjadi kesalahan:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $e)
                        <li>{{ $e }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <form action="{{ route('users.store') }}" method="POST" class="row g-3">
                    @csrf

                    <div class="col-12">
                        <label class="form-label small required">Nama</label>
                        <input type="text" name="name"
                            class="form-control form-control-sm @error('name') is-invalid @enderror"
                            value="{{ old('name') }}">
                    </div>

                    <div class="col-12">
                        <label class="form-label small required">Email</label>
                        <input type="email" name="email"
                            class="form-control form-control-sm @error('email') is-invalid @enderror"
                            value="{{ old('email') }}">
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small required">Password</label>
                        <input type="password" name="password"
                            class="form-control form-control-sm @error('password') is-invalid @enderror">
                        <div class="help">Minimal 6 karakter.</div>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small required">Konfirmasi Password</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-sm">
                    </div>

                    {{-- kalau pakai flag admin --}}
                    @if (Schema::hasColumn('users', 'is_admin'))
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" name="is_admin" id="is_admin" class="form-check-input"
                                    value="1" {{ old('is_admin') ? 'checked' : '' }}>
                                <label for="is_admin" class="form-check-label small">
                                    Jadikan user ini sebagai <strong>Admin/Owner</strong>
                                </label>
                            </div>
                            <div class="help">
                                Admin bisa mengakses semua modul & membuat user baru.
                            </div>
                        </div>
                    @endif

                    <div class="col-12 d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            Simpan User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
