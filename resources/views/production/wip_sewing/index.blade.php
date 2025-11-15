@extends('layouts.app')

@section('title', 'WIP Sewing – Dari Hasil QC Cutting')

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
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3">

        {{-- Header --}}
        <div class="d-flex align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">WIP Sewing</h1>
                <div class="text-muted small">
                    Daftar batch cutting yang sudah QC (qc_done) dan siap dijahit.
                </div>
            </div>
        </div>

        {{-- Flash message --}}
        @if (session('success'))
            <div class="alert alert-success py-2">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif
        @if (session('info'))
            <div class="alert alert-info py-2">{{ session('info') }}</div>
        @endif

        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="sticky">Batch</th>
                            <th class="sticky">QC Status</th>
                            <th class="sticky text-center">Sewing Batch</th>
                            <th class="sticky text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batches as $batch)
                            @php
                                $hasSewing = $batch->sewing_batches_count > 0;
                            @endphp
                            <tr>
                                <td class="mono">
                                    {{ $batch->code }}
                                </td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                                        {{ strtoupper($batch->qc_status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if ($hasSewing)
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            {{ $batch->sewing_batches_count }} batch
                                        </span>
                                    @else
                                        <span class="text-muted small">Belum ada</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if (!$hasSewing)
                                        <a href="{{ route('production.wip_sewing.create_from_batch', $batch) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-plus-circle me-1"></i>
                                            Buat Sewing Batch
                                        </a>
                                    @else
                                        @php
                                            $sewingBatch = $batch->sewingBatches()->latest()->first();
                                        @endphp
                                        @if ($sewingBatch)
                                            <a href="{{ route('production.wip_sewing.edit', $sewingBatch) }}"
                                                class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil-square me-1"></i>
                                                Input Hasil Sewing
                                            </a>
                                            <a href="{{ route('production.wip_sewing.show', $sewingBatch) }}"
                                                class="btn btn-sm btn-outline-light">
                                                <i class="bi bi-eye me-1"></i>
                                                Lihat
                                            </a>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">
                                    Belum ada batch dengan status <code>qc_done</code>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
