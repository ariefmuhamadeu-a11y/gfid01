@extends('layouts.app')

@section('title', 'Cutting • Kiriman Bahan')

@section('content')
    <div class="page-wrap py-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Kiriman Bahan ke Cutting</h1>

            <form method="GET" class="d-flex gap-2">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                    placeholder="Cari kode transfer…">
                <button class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
        @endif
        @if (session('info'))
            <div class="alert alert-info small">{{ session('info') }}</div>
        @endif

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Tanggal</th>
                            <th>Dari</th>
                            <th>Ke</th>
                            <th>Status</th>
                            <th>Batch</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfers as $t)
                            <tr>
                                <td class="mono">{{ $t->code }}</td>
                                <td>{{ $t->date?->format('d/m/Y') ?? '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $t->fromWarehouse->name ?? '-' }}</div>
                                    <div class="text-muted small mono">{{ $t->fromWarehouse->code ?? '' }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold">{{ $t->toWarehouse->name ?? '-' }}</div>
                                    <div class="text-muted small mono">{{ $t->toWarehouse->code ?? '' }}</div>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">{{ strtoupper($t->status) }}</span>
                                </td>
                                <td>
                                    @if ($t->productionBatch)
                                        <div class="mono">{{ $t->productionBatch->code }}</div>
                                    @else
                                        <span class="text-muted small">Belum ada</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if ($t->productionBatch)
                                        {{-- Kalau sudah punya batch → tombol Lihat Batch --}}
                                        <a href="{{ route('production.vendor_cutting.batches.show', $t->productionBatch->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i> Lihat Batch
                                        </a>
                                    @else
                                        {{-- Kalau belum → tombol Terima & Buat Batch --}}
                                        <a href="{{ route('production.vendor_cutting.receive.form', $t->id) }}"
                                            class="btn btn-sm btn-primary">
                                            <i class="bi bi-scissors"></i> Terima & Buat Batch
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small py-4">
                                    Belum ada kiriman bahan untuk cutting.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card-footer py-2">
                {{ $transfers->withQueryString()->links() }}
            </div>
        </div>

    </div>
@endsection
