<?php

namespace App\Http\Requests\Admin;

use App\Enums\StatusPengajuan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusPengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role->bolehVerifikasiPengajuan() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(StatusPengajuan::class)],
            'catatan' => ['nullable', 'string', 'max:1000', 'required_if:status,'.StatusPengajuan::PerluPerbaikan->value.','.StatusPengajuan::Ditolak->value],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan.required_if' => 'Catatan wajib diisi saat meminta perbaikan atau menolak pengajuan.',
        ];
    }

    public function statusTujuan(): StatusPengajuan
    {
        return StatusPengajuan::from($this->validated('status'));
    }
}
