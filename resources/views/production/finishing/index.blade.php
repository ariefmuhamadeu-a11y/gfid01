@extends('layouts.app')

@section('title', 'Finishing – Dari Hasil Sewing')

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

        <div class="d-flex align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">Finishing</h1>
                <div class="text-muted small">
                    Daftar Sewing Batch yang sudah selesai dan siap diproses Finishing.
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

        <div class="card p-3">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="sticky">Sewing Batch</th>
                            <th class="sticky">Cutting Batch</th>
                            <th class="sticky text-end">Total OK Sewing</th>
                            <th class="sticky text-center">Finishing</th>
                            <th class="sticky text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sewingDone as $sewing)
                            @php
                                $finishing = $sewing->finishingBatch;
                            @endphp
                            <tr>
                                <td class="mono">{{ $sewing->code }}</td>
                                <td class="mono">{{ $sewing->productionBatch?->code }}</td>
                                <td class="text-end mono">{{ $sewing->total_qty_ok }}</td>
                                <td class="text-center">
                                    @if ($finishing)
                                        @if ($finishing->status === 'done')
                                            <span class="badge bg-success">DONE</span>
                                        @else
                                            <span class="badge bg-warning">DRAFT</span>
                                        @endif
                                    @else
                                        <span class="text-muted small">Belum dibuat</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if (!$finishing)
                                        <a href="{{ route('production.finishing.create_from_sewing', $sewing) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-plus-circle me-1"></i>
                                            Buat Finishing Batch
                                        </a>
                                    @else
                                        <a href="{{ route('production.finishing.edit', $finishing) }}"
                                            class="btn btn-sm btn-outline-secondary me-1">
                                            <i class="bi bi-pencil-square me-1"></i>
                                            Input Finishing
                                        </a>
                                        <a href="{{ route('production.finishing.show', $finishing) }}"
                                            class="btn btn-sm btn-outline-light">
                                            <i class="bi bi-eye me-1"></i>
                                            Lihat
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    Belum ada Sewing Batch dengan status <code>done</code>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@endsection
