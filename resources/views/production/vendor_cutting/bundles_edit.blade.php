@extends('layouts.app')

@section('title', 'Cutting • Hasil Bundling ' . $batch->code)

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
            <a href="{{ $backRoute ?? route('production.vendor_cutting.batches.show', $batch->id) }}"
                class="btn btn-sm btn-outline-secondary me-2">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h1 class="h4 mb-0">Input Hasil Cutting per Iket</h1>
                <div class="help">
                    Batch: <span class="mono">{{ $batch->code }}</span> • Stage: {{ $batch->stage }} • Status:
                    {{ $batch->status }}
                </div>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger small">
                <strong>Terjadi kesalahan:</strong>
                <ul class="mb-0 mt-1">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Info singkat LOT kain di batch --}}
        <div class="card mb-3">
            <div class="p-3 border-bottom">
                <div class="fw-semibold">Bahan (LOT) di Batch</div>
                <div class="help">LOT kain yang boleh dipakai sebagai sumber bundling.</div>
            </div>
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
                                <td class="mono">{{ $m->lot->code ?? '-' }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $m->item->name ?? '-' }}</div>
                                    <div class="help mono">{{ $m->item_code }}</div>
                                </td>
                                <td class="text-end mono">{{ number_format($m->qty_planned, 2) }}</td>
                                <td>{{ $m->unit }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted small py-3">
                                    Tidak ada bahan di batch ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ $formAction ?? route('production.vendor_cutting.batches.results.update', $batch->id) }}">
            @csrf

            <div class="card mb-3">
                <div class="p-3 border-bottom">
                    <div class="fw-semibold">Daftar Iket / Bundle</div>
                    <div class="help">Isi hasil cutting per iket. Kamu bisa tambah/hapus baris.</div>
                </div>

                <div class="p-2 table-responsive">
                    <table class="table table-sm align-middle mb-0" id="bundles-table">
                        <thead>
                            <tr>
                                <th>LOT Sumber</th>
                                <th>Item Hasil</th>
                                <th>Kode Bundle</th>
                                <th>No</th>
                                <th class="text-end">Qty</th>
                                <th>Unit</th>
                                <th>Catatan</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $oldBundles = old('bundles', $bundles->toArray());
                            @endphp

                            @forelse ($oldBundles as $idx => $b)
                                <tr>
                                    <td>
                                        <select name="bundles[{{ $idx }}][lot_id]"
                                            class="form-select form-select-sm" required>
                                            <option value="">- pilih LOT -</option>
                                            @foreach ($lots as $lot)
                                                <option value="{{ $lot->id }}" @selected(($b['lot_id'] ?? ($b['lot']['id'] ?? null)) == $lot->id)>
                                                    {{ $lot->code }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="bundles[{{ $idx }}][item_id]"
                                            class="form-select form-select-sm" required>
                                            <option value="">- pilih item -</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}" @selected(($b['item_id'] ?? ($b['item']['id'] ?? null)) == $item->id)>
                                                    {{ $item->code }} - {{ $item->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="bundles[{{ $idx }}][bundle_code]"
                                            class="form-control form-control-sm mono" value="{{ $b['bundle_code'] ?? '' }}"
                                            placeholder="Auto jika dikosongkan">
                                    </td>
                                    <td>
                                        <input type="number" name="bundles[{{ $idx }}][bundle_no]"
                                            class="form-control form-control-sm" value="{{ $b['bundle_no'] ?? $idx + 1 }}">
                                    </td>
                                    <td class="text-end">
                                        <input type="number" step="1" min="1"
                                            name="bundles[{{ $idx }}][qty_cut]"
                                            class="form-control form-control-sm text-end mono"
                                            value="{{ $b['qty_cut'] ?? ($b['qty_cut'] ?? '') }}" required>
                                    </td>
                                    <td>
                                        <input type="text" name="bundles[{{ $idx }}][unit]"
                                            class="form-control form-control-sm" value="{{ $b['unit'] ?? 'pcs' }}">
                                    </td>
                                    <td>
                                        <input type="text" name="bundles[{{ $idx }}][notes]"
                                            class="form-control form-control-sm" value="{{ $b['notes'] ?? '' }}">
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                {{-- 1 baris kosong default --}}
                                <tr>
                                    <td>
                                        <select name="bundles[0][lot_id]" class="form-select form-select-sm" required>
                                            <option value="">- pilih LOT -</option>
                                            @foreach ($lots as $lot)
                                                <option value="{{ $lot->id }}">{{ $lot->code }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <select name="bundles[0][item_id]" class="form-select form-select-sm" required>
                                            <option value="">- pilih item -</option>
                                            @foreach ($items as $item)
                                                <option value="{{ $item->id }}">{{ $item->code }} -
                                                    {{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="bundles[0][bundle_code]"
                                            class="form-control form-control-sm mono" placeholder="Auto jika dikosongkan">
                                    </td>
                                    <td>
                                        <input type="number" name="bundles[0][bundle_no]"
                                            class="form-control form-control-sm" value="1">
                                    </td>
                                    <td class="text-end">
                                        <input type="number" step="1" min="1" name="bundles[0][qty_cut]"
                                            class="form-control form-control-sm text-end mono" required>
                                    </td>
                                    <td>
                                        <input type="text" name="bundles[0][unit]" class="form-control form-control-sm"
                                            value="pcs">
                                    </td>
                                    <td>
                                        <input type="text" name="bundles[0][notes]"
                                            class="form-control form-control-sm">
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3 border-top d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-row">
                        <i class="bi bi-plus-circle"></i> Tambah Baris
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save"></i> Simpan Hasil Cutting
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Simple JS untuk tambah/hapus baris --}}
    @push('scripts')
        <script>
            (function() {
                const tableBody = document.querySelector('#bundles-table tbody');
                const btnAddRow = document.querySelector('#btn-add-row');

                function bindRemoveButtons() {
                    document.querySelectorAll('.btn-remove-row').forEach(btn => {
                        btn.onclick = () => {
                            const rowCount = tableBody.rows.length;
                            if (rowCount > 1) {
                                btn.closest('tr').remove();
                            }
                        };
                    });
                }

                function getNextIndex() {
                    const rows = tableBody.querySelectorAll('tr');
                    return rows.length;
                }

                btnAddRow?.addEventListener('click', () => {
                    const idx = getNextIndex();
                    const lotsOptions =
                        `@foreach ($lots as $lot)<option value="{{ $lot->id }}">{{ $lot->code }}</option>@endforeach`;
                    const itemsOptions =
                        `@foreach ($items as $item)<option value="{{ $item->id }}">{{ $item->code }} - {{ $item->name }}</option>@endforeach`;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                <td>
                    <select name="bundles[${idx}][lot_id]" class="form-select form-select-sm" required>
                        <option value="">- pilih LOT -</option>
                        ${lotsOptions}
                    </select>
                </td>
                <td>
                    <select name="bundles[${idx}][item_id]" class="form-select form-select-sm" required>
                        <option value="">- pilih item -</option>
                        ${itemsOptions}
                    </select>
                </td>
                <td>
                    <input type="text" name="bundles[${idx}][bundle_code]"
                           class="form-control form-control-sm mono"
                           placeholder="Auto jika dikosongkan">
                </td>
                <td>
                    <input type="number" name="bundles[${idx}][bundle_no]"
                           class="form-control form-control-sm" value="${idx+1}">
                </td>
                <td class="text-end">
                    <input type="number" step="1" min="1"
                           name="bundles[${idx}][qty_cut]"
                           class="form-control form-control-sm text-end mono" required>
                </td>
                <td>
                    <input type="text" name="bundles[${idx}][unit]"
                           class="form-control form-control-sm" value="pcs">
                </td>
                <td>
                    <input type="text" name="bundles[${idx}][notes]"
                           class="form-control form-control-sm">
                </td>
                <td class="text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row">
                        <i class="bi bi-x"></i>
                    </button>
                </td>
            `;
                    tableBody.appendChild(tr);
                    bindRemoveButtons();
                });

                bindRemoveButtons();
            })();
        </script>
    @endpush
@endsection
