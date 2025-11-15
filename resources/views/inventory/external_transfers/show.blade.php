@extends('layouts.app')

@section('content')
    <div class="container py-4">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <a href="{{ route('inventory.external_transfers.index') }}" class="text-decoration-none small"
                    style="color: var(--text-muted);">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <h3 class="fw-bold mt-1" style="color: var(--text);">
                    Detail External Transfer
                </h3>
                <div class="small text-muted">
                    {{ $transfer->code }}
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                {{-- STATUS BADGE --}}
                @php
                    $status = $transfer->status;
                    $statusLabel =
                        [
                            'sent' => 'Dikirim',
                            'received' => 'Diterima',
                            'completed' => 'Selesai',
                            'cancelled' => 'Batal',
                        ][$status] ?? $status;

                    $statusClass = match ($status) {
                        'sent' => 'bg-warning text-dark',
                        'received' => 'bg-info text-dark',
                        'completed' => 'bg-success',
                        'cancelled' => 'bg-danger',
                        default => 'bg-secondary',
                    };
                @endphp

                <span class="badge {{ $statusClass }}">
                    {{ $statusLabel }}
                </span>

                <a href="{{ route('inventory.external_transfers.edit', $transfer->id) }}"
                    class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil-square"></i> Ubah Status
                </a>
            </div>
        </div>

        {{-- FLASH --}}
        @if (session('success'))
            <div class="alert alert-success py-2">
                {{ session('success') }}
            </div>
        @endif

        {{-- META CARD --}}
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <div class="card h-100 shadow-sm" style="background: var(--card); border-color: var(--line);">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3" style="color: var(--text-light);">
                            Informasi Dokumen
                        </h6>

                        <dl class="row mb-0 small">
                            <dt class="col-4 text-muted">Kode</dt>
                            <dd class="col-8">{{ $transfer->code }}</dd>

                            <dt class="col-4 text-muted">Tanggal</dt>
                            <dd class="col-8">{{ $transfer->date->format('d/m/Y') }}</dd>

                            <dt class="col-4 text-muted">Dari Gudang</dt>
                            <dd class="col-8">
                                {{ $transfer->fromWarehouse?->code }} - {{ $transfer->fromWarehouse?->name }}
                            </dd>

                            <dt class="col-4 text-muted">Ke Gudang</dt>
                            <dd class="col-8">
                                {{ $transfer->toWarehouse?->code }} - {{ $transfer->toWarehouse?->name }}
                            </dd>

                            <dt class="col-4 text-muted">Catatan</dt>
                            <dd class="col-8">
                                {{ $transfer->notes ?: '-' }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>

            {{-- RINGKASAN LOT CHIP --}}
            <div class="col-md-6">
                <div class="card h-100 shadow-sm" style="background: var(--card); border-color: var(--line);">
                    <div class="card-body">
                        <h6 class="fw-semibold mb-3" style="color: var(--text-light);">
                            Ringkasan LOT
                        </h6>

                        @if ($transfer->lines->isEmpty())
                            <div class="text-muted small">Tidak ada detail LOT.</div>
                        @else
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($transfer->lines as $line)
                                    <x-lot-chip :lot-code="$line->lot->code" :item-code="$line->item_code" :qty="$line->qty" :unit="$line->unit" />
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- DETAIL LINES TABLE / MOBILE CARD --}}
        <div class="card shadow-sm" style="background: var(--card); border-color: var(--line);">
            <div class="card-body p-0">

                {{-- DESKTOP TABLE --}}
                <div class="d-none d-md-block">
                    <table class="table table-hover align-middle mb-0" style="color: var(--text);">
                        <thead class="table-light" style="background: var(--panel); color: var(--text-light);">
                            <tr>
                                <th class="px-3">LOT</th>
                                <th>Item</th>
                                <th class="text-end">Qty</th>
                                <th>Unit</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($transfer->lines as $line)
                                <tr style="border-color: var(--line);">
                                    <td class="px-3">
                                        <span class="fw-semibold">{{ $line->lot->code }}</span>
                                    </td>
                                    <td>
                                        {{ $line->item_code }}
                                        <div class="small text-muted">
                                            {{ $line->item?->name }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        {{ rtrim(rtrim(number_format($line->qty, 4, ',', '.'), '0'), ',') }}
                                    </td>
                                    <td>{{ $line->unit }}</td>
                                    <td class="small text-muted">
                                        {{ $line->notes ?: '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE CARD VIEW --}}
                <div class="d-md-none p-2">
                    @forelse ($transfer->lines as $line)
                        <div class="mb-2 p-2 rounded-3"
                            style="border: 1px solid var(--line); background: rgba(255,255,255,0.01);">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <div>
                                    <span class="fw-semibold">{{ $line->lot->code }}</span>
                                    <div class="small text-muted">{{ $line->item_code }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold">
                                        {{ rtrim(rtrim(number_format($line->qty, 4, ',', '.'), '0'), ',') }}
                                        {{ $line->unit }}
                                    </div>
                                </div>
                            </div>
                            @if ($line->notes)
                                <div class="small text-muted">
                                    {{ $line->notes }}
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center text-muted small py-3">
                            Tidak ada detail LOT.
                        </div>
                    @endforelse
                </div>

            </div>
        </div>

    </div>
@endsection
