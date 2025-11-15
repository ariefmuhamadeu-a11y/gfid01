@extends('layouts.app')

@section('title', 'QC Cutting • Daftar Batch')

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

        <div class="d-flex align-items-center mb-3">
            <div>
                <h1 class="h4 mb-0">QC Cutting</h1>
                <div class="help">Batch cutting yang menunggu proses QC.</div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success small">
                {{ session('success') }}
            </div>
        @endif

        <div class="card">
            <div class="table-responsive" style="max-height: 460px;">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="sticky">Tanggal</th>
                            <th class="sticky">Batch</th>
                            <th class="sticky">Operator</th>
                            <th class="sticky">External Transfer</th>
                            <th class="sticky text-center"># Bundles</th>
                            <th class="sticky text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batches as $batch)
                            <tr>
                                <td>{{ $batch->date_received?->format('d M Y') ?? '-' }}</td>
                                <td class="mono">{{ $batch->code }}</td>
                                <td>{{ $batch->operator_code }}</td>
                                <td class="mono">{{ $batch->externalTransfer->code ?? '-' }}</td>
                                <td class="text-center mono">{{ $batch->bundles_count }}</td>
                                <td class="text-end">
                                    <a href="{{ route('production.wip_cutting_qc.edit', $batch->id) }}"
                                        class="btn btn-sm btn-primary">
                                        <i class="bi bi-check2-square"></i>
                                        QC
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted small py-3">
                                    Tidak ada batch yang menunggu QC.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($batches->hasPages())
                <div class="p-2 border-top">
                    {{ $batches->links() }}
                </div>
            @endif
        </div>

    </div>
@endsection
