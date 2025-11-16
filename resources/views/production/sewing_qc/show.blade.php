@extends('layouts.app')

@section('title', 'QC Sewing - ' . ($bundle->bundle_code ?? $bundle->id))

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="{{ route('production.wip_sewing_qc.index') }}" class="text-decoration-none small"
                    style="color: var(--text-muted);"><i class="bi bi-arrow-left"></i> Kembali</a>
                <h4 class="mb-0">QC Sewing - {{ $bundle->bundle_code ?? $bundle->id }}</h4>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="card mb-3" style="background: var(--card); border-color: var(--line);">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="small text-muted">Item</div>
                        <div class="fw-semibold">{{ $bundle->item?->code }} — {{ $bundle->item?->name }}</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Qty QC Cutting</div>
                        <div class="fw-semibold">{{ number_format((float) $bundle->qty_ok, 2) }} pcs</div>
                    </div>
                    <div class="col-md-4">
                        <div class="small text-muted">Status</div>
                        <span class="badge bg-info text-dark">{{ $bundle->sewing_status }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card" style="background: var(--card); border-color: var(--line);">
            <div class="card-body">
                <form action="{{ route('production.wip_sewing_qc.update', $bundle) }}" method="post">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small">Qty OK</label>
                            <input type="number" name="qty_ok" min="0" step="0.01" class="form-control"
                                value="{{ old('qty_ok', $bundle->qty_ok - $bundle->qty_sewn_ok - $bundle->qty_sewn_reject) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Qty Reject</label>
                            <input type="number" name="qty_reject" min="0" step="0.01" class="form-control"
                                value="{{ old('qty_reject', 0) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Catatan</label>
                            <input type="text" name="note" class="form-control" value="{{ old('note') }}">
                        </div>
                    </div>

                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div class="small text-muted">Qty tersisa untuk QC: {{ number_format(max(0, $bundle->qty_ok - $bundle->qty_sewn_ok - $bundle->qty_sewn_reject), 2) }} pcs</div>
                        <button class="btn btn-primary" type="submit">Simpan QC</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3" style="background: var(--card); border-color: var(--line);">
            <div class="card-body">
                <div class="fw-semibold mb-2">Riwayat QC</div>
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th class="text-end">Qty OK</th>
                            <th class="text-end">Qty Reject</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bundle->sewingQcLines as $line)
                            <tr>
                                <td>{{ optional($line->qc_date)->format('d/m/Y') }}</td>
                                <td class="text-end">{{ number_format((float) $line->qty_ok, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->qty_reject, 2) }}</td>
                                <td class="small text-muted">{{ $line->note }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Belum ada riwayat QC.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
