<?php

namespace App\Http\Requests\Purchasing;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchasePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // kalau nanti mau pakai gate/policy bisa diganti
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank,transfer,other'],
            'ref_no' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Tanggal pembayaran wajib diisi.',
            'amount.required' => 'Nominal wajib diisi.',
            'amount.min' => 'Nominal tidak boleh nol.',
            'method.required' => 'Pilih metode pembayaran.',
        ];
    }
}
