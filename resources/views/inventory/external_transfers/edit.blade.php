@extends('layouts.app')

@section('content')
    <div class="container py-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="{{ route('inventory.external_transfers.show', $transfer->id) }}" class="text-decoration-none small"
                    style="color: var(--text-muted);">
                    <i class="bi bi-arrow-left"></i> Kembali ke Detail
                </a>
                <h3 class="fw-bold mt-1" style="color: var(--text);">
                    Ubah Status External Transfer
                </h3>
                <div class="small text-muted">
                    {{ $transfer->code }}
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- STATUS TIMELINE SEDERHANA --}}
            <div class="col-md-4">
                <div class="card h-100 shadow-sm" style="background: var(--card); border-color: var(--line);">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3" style="color: var(--text-light);">
                            Tracking Status
                        </h6>

                        @php
                            $steps = [
                                'sent' => 'Dikirim',
                                'received' => 'Diterima Operator',
                                'completed' => 'Selesai',
                            ];

                            $current = $transfer->status;
                        @endphp

                        <ol class="list-unstyled small mb-0">
                            @foreach ($steps as $key => $label)
                                @php
                                    $isDone =
                                        in_array($key, ['sent', 'received', 'completed']) &&
                                        array_search($key, array_keys($steps)) <=
                                            array_search($current, array_keys($steps));
                                @endphp
                                <li class="d-flex align-items-center mb-2">
                                    <span
                                        class="me-2 rounded-circle d-inline-flex align-items-center justify-content-center"
                                        style="
                                        width: 18px;
                                        height: 18px;
                                        font-size: 11px;
                                        border: 2px solid {{ $isDone ? 'var(--accent, #0d6efd)' : 'var(--line, #4b5563)' }};
                                        background: {{ $isDone ? 'var(--accent, #0d6efd)' : 'transparent' }};
                                        color: {{ $isDone ? '#fff' : 'var(--text-muted, #9ca3af)' }};
                                      ">
                                        {{ $loop->iteration }}
                                    </span>
                                    <span style="color: {{ $isDone ? 'var(--text)' : 'var(--text-muted)' }};">
                                        {{ $label }}
                                    </span>
                                </li>
                            @endforeach
                        </ol>

                        <p class="small text-muted mt-3 mb-0">
                            Pengubahan status tidak mengubah stok (stok sudah dipindahkan penuh saat dokumen dibuat).
                        </p>
                    </div>
                </div>
            </div>

            {{-- FORM UBah STATUS --}}
            <div class="col-md-8">
                <div class="card shadow-sm" style="background: var(--card); border-color: var(--line);">
                    <div class="card-body">
                        <form action="{{ route('inventory.external_transfers.update', $transfer->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="sent"
                                        {{ old('status', $transfer->status) === 'sent' ? 'selected' : '' }}>
                                        Dikirim
                                    </option>
                                    <option value="received"
                                        {{ old('status', $transfer->status) === 'received' ? 'selected' : '' }}>
                                        Diterima Operator
                                    </option>
                                    <option value="completed"
                                        {{ old('status', $transfer->status) === 'completed' ? 'selected' : '' }}>
                                        Selesai
                                    </option>
                                    <option value="cancelled"
                                        {{ old('status', $transfer->status) === 'cancelled' ? 'selected' : '' }}>
                                        Batal
                                    </option>
                                </select>
                                @error('status')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Catatan</label>
                                <textarea name="notes" rows="3" class="form-control">{{ old('notes', $transfer->notes) }}</textarea>
                                @error('notes')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Simpan
                            </button>
                            <a href="{{ route('inventory.external_transfers.show', $transfer->id) }}"
                                class="btn btn-outline-secondary ms-1">
                                Batal
                            </a>
                        </form>
                    </div>
                </div>
            </div>

        </div>

    </div>
@endsection
