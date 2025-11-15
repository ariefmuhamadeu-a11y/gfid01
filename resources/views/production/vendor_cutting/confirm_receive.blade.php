@extends('layouts.app')

@section('title', 'Cutting • Terima Bahan')

@push('head')
    <style>
        .page-wrap {
            max-width: 960px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        th.sticky {
            position: sticky;
            top: 0;
            background: var(--card);
            z-index: 1;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3">
        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('production.vendor_cutting.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1 class="h4 mb-0">Terima Bahan Cutting</h1>
                <div class="help">
                    External Transfer: <span class="mono">{{ $transfer->code }}</span>
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger small">
                <ul class="mb-0">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-3 p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="help">No. Transfer</div>
                    <div class="mono fw-semibold">{{ $transfer->code }}</div>
                </div>
                <div class="col-md-4">
                    <div class="help">Dari Gudang</div>
                    <div class="fw-semibold">{{ $transfer->fromWarehouse->name ?? '-' }}</div>
                    <div class="help mono">{{ $transfer->fromWarehouse->code ?? '' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="help">Ke Gudang</div>
                    <div class="fw-semibold">{{ $transfer->toWarehouse->name ?? '-' }}</div>
                    <div class="help mono">{{ $transfer->toWarehouse->code ?? '' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="help">Tanggal Transfer</div>
                    <div>{{ $transfer->date?->format('d M Y') ?? '-' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="help">Status</div>
                    <span class="badge bg-secondary">{{ $transfer->status }}</span>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="p-3 border-bottom">
                <div class="fw-semibold">Daftar LOT yang dikirim</div>
                <div class="help">Semua LOT berikut akan dimasukkan ke dalam 1 batch cutting.</div>
            </div>
            <div class="table-responsive" style="max-height: 320px;">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="sticky">LOT</th>
                            <th class="sticky">Item</th>
                            <th class="sticky text-end">Qty</th>
                            <th class="sticky">Unit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transfer->lines as $line)
                            <tr>
                                <td class="mono">{{ $line->lot->code ?? '-' }}</td>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $line->item->name ?? ($line->item_name ?? '-') }}
                                    </div>
                                    <div class="help mono">{{ $line->item_code }}</div>
                                </td>
                                <td class="text-end mono">{{ number_format($line->qty, 2) }}</td>
                                <td>{{ $line->unit }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    Tidak ada data LOT.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('production.vendor_cutting.receive.store', $transfer->id) }}">
            @csrf

            <div class="card mb-3 p-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label form-label-sm">Tanggal diterima</label>
                        <input type="date" name="date_received" class="form-control form-control-sm"
                            value="{{ old('date_received', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label form-label-sm">Catatan (opsional)</label>
                        <textarea name="notes" rows="2" class="form-control form-control-sm"
                            placeholder="Misal: kain sudah dicek, aman...">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div class="help">
                        Setelah disimpan, sistem akan membuat <strong>Batch Cutting</strong>
                        dari semua LOT di atas.
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-scissors"></i>
                            Buat Batch Cutting
                        </button>
                    </div>
                </div>
            </div>
        </form>

    </div>
@endsection
