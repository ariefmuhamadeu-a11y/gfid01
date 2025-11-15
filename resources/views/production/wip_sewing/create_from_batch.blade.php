@extends('layouts.app')

@section('title', 'Buat Sewing Batch dari QC • ' . $batch->code)

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

        .help {
            color: var(--muted);
            font-size: .85rem;
        }
    </style>
@endpush

@section('content')
    <div class="page-wrap py-3">

        <div class="d-flex align-items-center mb-3">
            <a href="{{ route('production.wip_sewing.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1 class="h5 mb-0">Buat Sewing Batch dari QC</h1>
                <div class="text-muted small">
                    Batch Cutting: <span class="mono">{{ $batch->code }}</span> • Status QC:
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        {{ strtoupper($batch->qc_status) }}
                    </span>
                </div>
            </div>
        </div>

        @if (session('error'))
            <div class="alert alert-danger py-2">{{ session('error') }}</div>
        @endif

        <div class="card p-3 mb-3">
            <dl class="row mb-0 small">
                <dt class="col-sm-3">Kode Sewing (baru)</dt>
                <dd class="col-sm-9 mono">{{ $code }}</dd>

                @if ($employee)
                    <dt class="col-sm-3">Operator Sewing (login)</dt>
                    <dd class="col-sm-9">
                        {{ $employee->name }} <span class="mono">({{ $employee->code }})</span>
                    </dd>
                @else
                    <dt class="col-sm-3">Operator Sewing</dt>
                    <dd class="col-sm-9 text-muted">
                        Tidak terhubung ke employee. Sistem akan membuat tanpa employee_id.
                    </dd>
                @endif
            </dl>
        </div>

        <div class="card p-3 mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="fw-semibold">Ringkasan Bundle OK yang akan dijahit</div>
                <div class="help mb-0">
                    Hanya bundle dengan <code>qty_ok &gt; 0</code> yang akan dibuatkan baris sewing.
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="sticky">Bundle</th>
                            <th class="sticky text-end">Qty Cut</th>
                            <th class="sticky text-end">Qty OK (QC)</th>
                            <th class="sticky text-end">Qty Reject (QC)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalInput = 0;
                        @endphp
                        @foreach ($batch->cuttingBundles as $bundle)
                            @php
                                $totalInput += $bundle->qty_ok;
                            @endphp
                            <tr>
                                <td class="mono">{{ $bundle->code ?? 'BND-' . $bundle->id }}</td>
                                <td class="text-end mono">{{ $bundle->qty_cut }}</td>
                                <td class="text-end mono">{{ $bundle->qty_ok }}</td>
                                <td class="text-end mono text-danger">{{ $bundle->qty_reject }}</td>
                            </tr>
                        @endforeach
                        <tr class="fw-semibold border-top">
                            <td class="text-end">Total OK (siap dijahit)</td>
                            <td></td>
                            <td class="text-end mono">{{ $totalInput }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('production.wip_sewing.store_from_batch', $batch) }}">
            @csrf

            {{-- Kalau mau pakai employee lain, bisa tambahkan select di sini nanti --}}
            <div class="d-flex justify-content-between align-items-center">
                <div class="help">
                    Setelah dikonfirmasi, sistem akan membuat <strong>1 Sewing Batch</strong> dan
                    <strong>1 baris Sewing per bundle OK</strong>.
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check2-circle me-1"></i>
                    Konfirmasi &amp; Buat Sewing Batch
                </button>
            </div>
        </form>

    </div>
@endsection
