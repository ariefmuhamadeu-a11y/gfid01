@extends('layouts.app')

@section('title', 'QC WIP Cutting • ' . $wip->item_code)

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
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <a href="{{ route('wip_cutting_qc.index') }}" class="btn btn-sm btn-outline-secondary">
                &larr; Kembali
            </a>
            <div class="text-muted small">
                WIP ID: {{ $wip->id }}
            </div>
        </div>

        <div class="row g-3">
            {{-- LEFT: Info WIP --}}
            <div class="col-12 col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="small-label mb-2">Info WIP Cutting</div>

                        <div class="mb-2">
                            <div class="small-label">Item</div>
                            <div class="fw-semibold">
                                {{ $wip->item_code }}
                            </div>
                            <div class="text-muted small">
                                {{ $wip->item?->name ?? '-' }}
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="small-label">Qty WIP Saat ini</div>
                            <div class="mono fw-semibold">
                                {{ number_format($wip->qty, 2) }} pcs
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="small-label">Gudang</div>
                            <div class="fw-semibold">
                                {{ $wip->warehouse?->code ?? '-' }}
                            </div>
                            <div class="text-muted small">
                                {{ $wip->warehouse?->name ?? '' }}
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="small-label">Batch Cutting</div>
                            @if ($wip->productionBatch)
                                <div class="mono fw-semibold">
                                    {{ $wip->productionBatch->code }}
                                </div>
                                <div class="text-muted small">
                                    {{ optional($wip->productionBatch->date)->format('d M Y') }}
                                </div>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </div>

                        <div class="mb-2">
                            <div class="small-label">QC Status</div>
                            <span class="badge bg-warning text-dark">
                                {{ strtoupper($wip->qc_status ?? 'pending') }}
                            </span>
                            @if ($wip->qc_notes)
                                <div class="text-muted small mt-1">
                                    Catatan terakhir: {{ $wip->qc_notes }}
                                </div>
                            @endif
                        </div>

                        @if ($wip->components->count())
                            <div class="mt-3">
                                <div class="small-label mb-1">Komponen yang sudah terpasang</div>
                                <ul class="small mb-0">
                                    @foreach ($wip->components as $comp)
                                        <li>
                                            {{ $comp->type ?? $comp->item_code }}
                                            &times; {{ number_format($comp->qty, 2) }} {{ $comp->unit }}
                                            (LOT: {{ $comp->lot?->code ?? $comp->lot_id }})
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- RIGHT: Form QC --}}
            <div class="col-12 col-lg-8">
                <form action="{{ route('wip_cutting_qc.update', $wip->id) }}" method="post">
                    @csrf
                    @method('PUT')

                    <div class="card mb-3">
                        <div class="card-body">
                            <h2 class="h5 mb-3">QC & Kitting WIP Cutting</h2>

                            @if ($errors->any())
                                <div class="alert alert-danger small">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $err)
                                            <li>{{ $err }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="row g-3 mb-3">
                                <div class="col-6 col-md-4">
                                    <label class="form-label small-label">Qty OK (pcs)</label>
                                    <input type="number" name="qty_ok" value="{{ old('qty_ok', (int) $wip->qty) }}"
                                        class="form-control mono" min="0" step="1">
                                    <div class="text-muted small">
                                        Maks: {{ (int) $wip->qty }} pcs
                                    </div>
                                </div>
                                <div class="col-6 col-md-4">
                                    <label class="form-label small-label">Qty Reject (opsional)</label>
                                    <input type="number" name="qty_reject" value="{{ old('qty_reject') }}"
                                        class="form-control mono" min="0" step="1">
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label small-label">Status QC</label>
                                    <select name="qc_status" class="form-select form-select-sm">
                                        <option value="approved"
                                            {{ old('qc_status', 'approved') === 'approved' ? 'selected' : '' }}>Approved
                                        </option>
                                        <option value="rejected" {{ old('qc_status') === 'rejected' ? 'selected' : '' }}>
                                            Rejected</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small-label">Catatan QC</label>
                                <textarea name="qc_notes" rows="2" class="form-control" placeholder="Catatan hasil QC (opsional)">{{ old('qc_notes', $wip->qc_notes) }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Komponen / Bahan Pendukung --}}
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="small-label">Bahan Pendukung (rib, karet, dll.)</div>
                                <button type="button" class="btn btn-sm btn-outline-secondary"
                                    onclick="qcAddComponentRow()">
                                    + Tambah Komponen
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle mb-0" id="components-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 25%">LOT ID</th>
                                            <th style="width: 25%">Qty</th>
                                            <th style="width: 20%">Unit</th>
                                            <th style="width: 20%">Tipe (rib/karet)</th>
                                            <th style="width: 10%"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {{-- baris dinamis via JS --}}
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-muted small mt-2">
                                Catatan: LOT rib/karet harus sudah dibuat & punya stok di gudang ini.
                                Sistem akan otomatis mengurangi stok per LOT sesuai qty di atas.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            Simpan QC & Kitting
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- JS super simple untuk nambah/hapus baris komponen --}}
    @push('scripts')
        <script>
            function qcAddComponentRow() {
                const tbody = document.querySelector('#components-table tbody');
                const idx = tbody.children.length;

                const tr = document.createElement('tr');
                tr.innerHTML = `
            <td>
                <input type="number" name="components[${idx}][lot_id]" class="form-control form-control-sm mono"
                       placeholder="ID LOT" min="1">
            </td>
            <td>
                <input type="number" name="components[${idx}][qty]" class="form-control form-control-sm mono"
                       placeholder="Qty" step="0.0001" min="0">
            </td>
            <td>
                <input type="text" name="components[${idx}][unit]" class="form-control form-control-sm mono"
                       placeholder="pcs/m/kg">
            </td>
            <td>
                <input type="text" name="components[${idx}][type]" class="form-control form-control-sm"
                       placeholder="rib/karet">
            </td>
            <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">
                    &times;
                </button>
            </td>
        `;
                tbody.appendChild(tr);
            }
        </script>
    @endpush
@endsection
