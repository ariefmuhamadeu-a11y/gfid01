@extends('layouts.app')

@section('title', 'QC Cutting • ' . $batch->code)

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

        {{-- HEADER --}}
        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('production.wip_cutting_qc.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i>
            </a>

            <div>
                <h1 class="h4 mb-0">QC Cutting – {{ $batch->code }}</h1>
                <div class="help">
                    Stage: {{ $batch->stage }} • Status: {{ $batch->status }}
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
        @endif

        {{-- BATCH INFO --}}
        <div class="card mb-3 p-3">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Kode Batch</div>
                    <div class="mono fw-semibold">{{ $batch->code }}</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">Operator Cutting</div>
                    <div class="fw-semibold">{{ $batch->operator_code }}</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">Tanggal Terima</div>
                    <div>{{ $batch->date_received?->format('d M Y') }}</div>
                </div>
            </div>
        </div>


        {{-- BAHAN LOT --}}
        <div class="card mb-3">
            <div class="p-3 border-bottom fw-semibold">Bahan (LOT) di Batch</div>

            <div class="table-responsive" style="max-height: 220px;">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="sticky">LOT</th>
                            <th class="sticky">Item</th>
                            <th class="sticky text-end">Qty Planned</th>
                            <th class="sticky">Unit</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($batch->materials as $m)
                            <tr>
                                <td class="mono">{{ $m->lot->code }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $m->item->name }}</div>
                                    <div class="help mono">{{ $m->item_code }}</div>
                                </td>
                                <td class="text-end mono">{{ number_format($m->qty_planned, 2) }}</td>
                                <td>{{ $m->unit }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3 small">Tidak ada data bahan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>


        {{-- RINGKASAN QC --}}
        @php
            $totalOk = $batch->bundles->sum('qty_ok');
            $totalReject = $batch->bundles->sum('qty_reject');
        @endphp

        <div class="card mb-3 p-3">
            <div class="fw-semibold mb-2">Ringkasan QC</div>

            <div class="row">
                <div class="col-md-4">
                    <div class="text-muted small">Total OK</div>
                    <div class="mono fw-semibold text-success">{{ number_format($totalOk, 0) }} pcs</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">Total Reject</div>
                    <div class="mono fw-semibold text-danger">{{ number_format($totalReject, 0) }} pcs</div>
                </div>

                <div class="col-md-4">
                    <div class="text-muted small">Jumlah Bundle</div>
                    <div class="mono fw-semibold">{{ $batch->bundles->count() }} iket</div>
                </div>
            </div>
        </div>


        {{-- DAFTAR BUNDLE --}}
        <div class="card mb-3">
            <div class="p-3 border-bottom fw-semibold">Detail Iket / Bundle</div>

            <div class="table-responsive" style="max-height: 400px;">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="sticky">Bundle</th>
                            <th class="sticky">LOT</th>
                            <th class="sticky">Item</th>
                            <th class="sticky text-end">Cut</th>
                            <th class="sticky text-end text-success">OK</th>
                            <th class="sticky text-end text-danger">Reject</th>
                            <th class="sticky">Catatan QC</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($batch->bundles as $b)
                            <tr>
                                <td>
                                    <div class="mono fw-semibold">{{ $b->bundle_code }}</div>
                                    <div class="help">No: {{ $b->bundle_no }}</div>
                                </td>

                                <td class="mono">{{ $b->lot->code }}</td>

                                <td>
                                    <div class="fw-semibold">{{ $b->item->name }}</div>
                                    <div class="help mono">{{ $b->item_code }}</div>
                                </td>

                                <td class="text-end mono">{{ number_format($b->qty_cut, 0) }}</td>

                                <td class="text-end mono text-success">
                                    {{ number_format($b->qty_ok, 0) }}
                                </td>

                                <td class="text-end mono text-danger">
                                    {{ number_format($b->qty_reject, 0) }}
                                </td>

                                <td>{{ $b->notes }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small py-3">Belum ada bundle.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>


        {{-- RINGKASAN PER ITEM --}}
        <div class="card mb-4">
            <div class="p-3 border-bottom fw-semibold">Rekap per Item</div>

            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-end">Total OK</th>
                        <th class="text-end">Total Reject</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $group = $batch->bundles->groupBy('item_code');
                    @endphp

                    @foreach ($group as $itemCode => $rows)
                        @php
                            $sumOk = $rows->sum('qty_ok');
                            $sumReject = $rows->sum('qty_reject');
                        @endphp
                        <tr>
                            <td>
                                <span class="fw-semibold">{{ $rows->first()->item->name }}</span>
                                <div class="help mono">{{ $itemCode }}</div>
                            </td>
                            <td class="text-end mono text-success">{{ number_format($sumOk, 0) }} pcs</td>
                            <td class="text-end mono text-danger">{{ number_format($sumReject, 0) }} pcs</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>
@endsection
