@extends('layouts.app')

@section('title', 'QC Sewing')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">QC Sewing</h4>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <div class="card" style="background: var(--card); border-color: var(--line);">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light" style="background: var(--panel); color: var(--text-light);">
                        <tr>
                            <th class="px-3">Bundle</th>
                            <th>Item</th>
                            <th class="text-end">Qty OK</th>
                            <th>Status</th>
                            <th class="text-end px-3">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bundles as $bundle)
                            <tr>
                                <td class="px-3">{{ $bundle->bundle_code ?? 'Bundle #' . $bundle->id }}</td>
                                <td>
                                    <div class="mono small">{{ $bundle->item?->code }}</div>
                                    <div class="small text-muted">{{ $bundle->item?->name }}</div>
                                </td>
                                <td class="text-end">{{ number_format((float) $bundle->qty_ok, 2) }}</td>
                                <td><span class="badge bg-info text-dark">{{ $bundle->sewing_status }}</span></td>
                                <td class="text-end px-3">
                                    <a href="{{ route('production.wip_sewing_qc.show', $bundle) }}"
                                        class="btn btn-sm btn-primary">QC</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">Belum ada bundle untuk QC sewing.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3">
            {{ $bundles->links() }}
        </div>
    </div>
@endsection
