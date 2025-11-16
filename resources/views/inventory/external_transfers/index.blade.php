@extends('layouts.app')

@section('content')
    <div class="container py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold" style="color: var(--text);">
                External Transfer (Kirim Bahan)
            </h3>
            <a href="{{ route('inventory.external_transfers.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Buat Transfer
            </a>
        </div>

        {{-- DESKTOP TABLE --}}
        <div class="card shadow-sm d-none d-md-block" style="background: var(--card); border-color: var(--line);">
            <div class="card-body p-0">

                <table class="table table-hover align-middle mb-0" style="color: var(--text);">
                    <thead class="table-light" style="background: var(--panel); color: var(--text-light);">
                        <tr>
                            <th class="px-3">Tanggal</th>
                            <th>Kode</th>
                            <th>Dari</th>
                            <th>Ke</th>
                            <th>Status</th>
                            <th class="text-end px-3">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($transfers as $t)
                            @php
                                $status = $t->status;
                                $label =
                                    [
                                        'sent' => 'Dikirim',
                                        'received' => 'Diterima',
                                        'completed' => 'Selesai',
                                        'cancelled' => 'Batal',
                                    ][$status] ?? $status;

                                $class = match ($status) {
                                    'sent' => 'bg-warning text-dark',
                                    'received' => 'bg-info text-dark',
                                    'completed' => 'bg-success',
                                    'cancelled' => 'bg-danger',
                                    default => 'bg-secondary',
                                };
                            @endphp

                            <tr style="border-color: var(--line);">
                                <td class="px-3">
                                    {{ $t->date->format('d/m/Y') }}
                                </td>
                                <td>
                                    <span class="fw-semibold">{{ $t->code }}</span>
                                    <div class="small text-muted">{{ $t->transfer_type === 'sewing_bundle' ? 'Sewing Bundle' : 'Material' }}</div>
                                </td>
                                <td>{{ $t->fromWarehouse->code }}</td>
                                <td>{{ $t->toWarehouse->code }}</td>
                                <td>
                                    <span class="badge {{ $class }}">{{ $label }}</span>
                                </td>
                                <td class="text-end px-3">
                                    <a href="{{ route('inventory.external_transfers.show', $t->id) }}"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    Belum ada data transfer.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>

            </div>
        </div>

        {{-- MOBILE CARD LIST --}}
        <div class="d-md-none">
            @forelse ($transfers as $t)
                @php
                    $status = $t->status;
                    $label =
                        [
                            'sent' => 'Dikirim',
                            'received' => 'Diterima',
                            'completed' => 'Selesai',
                            'cancelled' => 'Batal',
                        ][$status] ?? $status;

                    $class = match ($status) {
                        'sent' => 'bg-warning text-dark',
                        'received' => 'bg-info text-dark',
                        'completed' => 'bg-success',
                        'cancelled' => 'bg-danger',
                        default => 'bg-secondary',
                    };
                @endphp

                <div class="mb-2 p-3 rounded-3" style="background: var(--card); border: 1px solid var(--line);">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <div class="small text-muted">
                                    {{ $t->date->format('d/m/Y') }}
                                </div>
                                <div class="fw-semibold">
                                    {{ $t->code }}
                                </div>
                                <div class="small text-muted">{{ $t->transfer_type === 'sewing_bundle' ? 'Sewing Bundle' : 'Material' }}</div>
                            </div>
                            <span class="badge {{ $class }}">{{ $label }}</span>
                        </div>

                    <div class="small text-muted mb-2">
                        {{ $t->fromWarehouse->code }} → {{ $t->toWarehouse->code }}
                    </div>

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('inventory.external_transfers.show', $t->id) }}"
                            class="btn btn-sm btn-outline-primary">
                            Detail
                        </a>
                    </div>
                </div>
            @empty
                <div class="text-center text-muted small py-4">
                    Belum ada data transfer.
                </div>
            @endforelse
        </div>

        {{-- PAGINATION --}}
        <div class="mt-3">
            {{ $transfers->links() }}
        </div>

    </div>
@endsection
