@extends('layouts.app')

@section('title', 'Produksi • QC WIP Cutting')

@push('head')
    <style>
        .qc-page .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
        }

        .qc-page .mono {
            font-variant-numeric: tabular-nums;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
        }

        .small-label {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: var(--muted);
        }
    </style>
@endpush

@section('content')
    <div class="qc-page container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">QC WIP Cutting</h1>
                <div class="text-muted small">
                    WIP hasil cutting yang menunggu QC & pelengkapan bahan (rib, karet, dll).
                </div>
            </div>

            <form method="get" class="d-flex gap-2">
                <input type="text" name="item_code" value="{{ request('item_code') }}"
                    class="form-control form-control-sm mono" placeholder="Cari kode item...">
                <button class="btn btn-sm btn-outline-secondary">Filter</button>
            </form>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Kode Item</th>
                                <th>Nama Item</th>
                                <th class="text-end">Qty WIP</th>
                                <th>Gudang</th>
                                <th>Batch Cutting</th>
                                <th>QC Status</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($wips as $wip)
                                <tr>
                                    <td class="mono">{{ $wip->item_code }}</td>
                                    <td>
                                        <div class="fw-semibold">{{ $wip->item?->name ?? '-' }}</div>
                                    </td>
                                    <td class="text-end mono">
                                        {{ number_format($wip->qty, 2) }} pcs
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ $wip->warehouse?->code ?? '-' }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ $wip->warehouse?->name ?? '' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($wip->productionBatch)
                                            <div class="mono">
                                                {{ $wip->productionBatch->code }}
                                            </div>
                                            <div class="text-muted small">
                                                {{ optional($wip->productionBatch->date)->format('d M Y') }}
                                            </div>
                                        @else
                                            <span class="text-muted small">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            {{ strtoupper($wip->qc_status ?? 'pending') }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('wip_cutting_qc.edit', $wip->id) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            QC & Kitting
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Tidak ada WIP Cutting yang menunggu QC.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-2">
                    {{ $wips->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
