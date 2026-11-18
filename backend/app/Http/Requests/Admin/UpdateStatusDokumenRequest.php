<?php

namespace App\Http\Requests\Admin;

use App\Models\DokumenPengajuan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStatusDokumenRequest extends FormRequest
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
            'status_verifikasi' => [
                'required',
                Rule::in([DokumenPengajuan::TERVERIFIKASI, DokumenPengajuan::DITOLAK]),
            ],
        ];
    }
}
