@extends('layouts.app')

@section('title', 'Cutting • Bundles')

@section('content')
    <div class="page-wrap py-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h1 class="h4 mb-0">Daftar Cutting Bundles</h1>
            <form class="d-flex gap-2" method="GET">
                <input type="text" name="batch_id" class="form-control form-control-sm" placeholder="Filter Batch ID"
                    value="{{ request('batch_id') }}">
                <button class="btn btn-sm btn-outline-secondary" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Kode Bundle</th>
                            <th>Batch</th>
                            <th>Item</th>
                            <th>LOT</th>
                            <th class="text-end">Qty Cut</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bundles as $bundle)
                            <tr>
                                <td class="mono">{{ $bundle->bundle_code ?? '-' }}</td>
                                <td>
                                    <div class="mono">{{ $bundle->productionBatch?->code ?? '-' }}</div>
                                    <div class="text-muted small">ID: {{ $bundle->production_batch_id }}</div>
                                </td>
                                <td>
                                    <div class="mono">{{ $bundle->item?->code ?? '-' }}</div>
                                    <div class="text-muted small">{{ $bundle->item?->name ?? '' }}</div>
                                </td>
                                <td class="mono">{{ $bundle->lot?->code ?? '-' }}</td>
                                <td class="text-end mono">{{ number_format($bundle->qty_cut, 2, ',', '.') }}
                                    {{ $bundle->unit }}</td>
                                <td>{{ $bundle->status }}</td>
                                <td class="text-end">
                                    @if ($bundle->productionBatch)
                                        <a href="{{ route('production.vendor_cutting.batches.results.edit', $bundle->productionBatch->id ?? 0) }}"
                                            class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted small py-4">Belum ada data bundle.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer py-2">
                {{ $bundles->withQueryString()->links() }}
            </div>
        </div>
    </div>
@endsection
