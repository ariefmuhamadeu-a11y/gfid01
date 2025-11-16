@extends('layouts.app')

@section('title', 'Detail Sewing • ' . $sewingBatch->code)

@push('head')
    <style>
        .page-wrap {
            max-width: 1080px;
            margin-inline: auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono";
        }

        .help {
            color: var(--muted);
            font-size: .85rem;
        }

        th.sticky {
            position: sticky;
            top: 0;
            background: var(--card);
            z-index: 1;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3">

        {{-- Header --}}
        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('production.wip_sewing.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1 class="h4 mb-0">Detail Sewing – {{ $sewingBatch->code }}</h1>
                <div class="help">
                    Cutting Batch: {{ $sewingBatch->productionBatch->code ?? '-' }}
                </div>
                @if ($sewingBatch->employee)
                    <div class="help">
                        Operator: {{ $sewingBatch->employee->name }}
                        <span class="mono">({{ $sewingBatch->employee->code }})</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger small">{{ session('error') }}</div>
        @endif

        {{-- Ringkasan totals --}}
        <div class="card mb-3">
            <div class="p-3 border-bottom">
                <div class="fw-semibold">Ringkasan Qty Sewing</div>
                <div class="help">
                    Status:
                    @if ($sewingBatch->status === 'done')
                        <span class="badge bg-success">DONE</span>
                    @else
                        <span class="badge bg-warning text-dark">IN PROGRESS</span>
                    @endif
                </div>
            </div>
            <div class="p-3">
                <div class="row small">
                    <div class="col-sm-4 mb-2">
                        <div class="text-muted">Total Qty Input</div>
                        <div class="mono fs-5">{{ $sewingBatch->total_qty_input }}</div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="text-muted">Total Qty OK</div>
                        <div class="mono fs-5 text-success">{{ $sewingBatch->total_qty_ok }}</div>
                    </div>
                    <div class="col-sm-4 mb-2">
                        <div class="text-muted">Total Qty Reject</div>
                        <div class="mono fs-5 text-danger">{{ $sewingBatch->total_qty_reject }}</div>
                    </div>
                </div>

                <div class="row small mt-2">
                    <div class="col-sm-4">
                        <div class="text-muted">Mulai</div>
                        <div>
                            {{ $sewingBatch->started_at?->format('d M Y H:i') ?? '-' }}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="text-muted">Selesai</div>
                        <div>
                            {{ $sewingBatch->finished_at?->format('d M Y H:i') ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Detail per bundle: bundle, kode barang, qty ok, qty reject, catatan --}}
        <div class="card mb-3">
            <div class="p-3 border-bottom">
                <div class="fw-semibold">Hasil Sewing per Bundle</div>
                <div class="help">
                    Menampilkan ringkasan per iket / bundle dari hasil sewing.
                </div>
            </div>

            <div class="table-responsive" style="max-height: 420px;">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="sticky">Bundle</th>
                            <th class="sticky">Kode Barang</th>
                            <th class="sticky text-end">Qty OK</th>
                            <th class="sticky text-end">Qty Reject</th>
                            <th class="sticky">Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sewingBatch->lines as $line)
                            @php
                                $bundle = $line->cuttingBundle;

                            @endphp
                            <tr>
                                {{-- Bundle --}}
                                <td class="mono">
                                    {{ $bundle->code ?? 'BND-' . $bundle->id }}
                                </td>

                                {{-- Kode Barang --}}
                                <td>
                                    <div class="fw-semibold">
                                        {{ $bundle->item->name ?? '-' }}
                                    </div>
                                    <div class="help mono">
                                        {{ $bundle->item_code ?? '-' }}
                                    </div>
                                </td>

                                {{-- Qty OK --}}
                                <td class="text-end mono text-success">
                                    {{ $line->qty_ok }}
                                </td>

                                {{-- Qty Reject --}}
                                <td class="text-end mono text-danger">
                                    {{ $line->qty_reject }}
                                </td>

                                {{-- Catatan --}}
                                <td>
                                    {{ $line->note ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted small py-3">
                                    Tidak ada data bundle sewing.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Tombol complete (opsional, kalau masih IN PROGRESS) --}}
        @if ($sewingBatch->status !== 'done')
            <form method="POST" action="{{ route('production.wip_sewing.complete', $sewingBatch) }}">
                @csrf
                <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check2-circle me-1"></i>
                        Tandai Sewing Selesai
                    </button>
                </div>
            </form>
        @endif

    </div>
@endsection
