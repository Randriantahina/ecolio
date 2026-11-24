<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use App\Application\Finance\DTOs\EncaisserDto;
use App\Domain\Finance\Enums\ModePaiement;
use App\Domain\Finance\Models\Paiement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class EncaisserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Paiement::class);
    }

    public function rules(): array
    {
        return [
            'eleve_uuid' => ['required', 'uuid'],
            'lignes' => ['required', 'array', 'min:1', 'max:60'],
            'lignes.*.echeance_uuid' => ['required', 'uuid'],
            'lignes.*.montant_ar' => ['required', 'integer', 'min:1'],
            'mode' => ['required', Rule::enum(ModePaiement::class)],
            'date_paiement' => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:today'],
            'reference_externe' => ['nullable', 'string', 'max:100'],
            'client_uuid' => ['nullable', 'uuid'],
        ];
    }

    public function toDto(): EncaisserDto
    {
        $d = $this->validated();

        return new EncaisserDto(
            eleveUuid: $d['eleve_uuid'],
            lignes: $d['lignes'],
            mode: $d['mode'],
            datePaiement: $d['date_paiement'] ?? now()->toDateString(),
            referenceExterne: $d['reference_externe'] ?? null,
            clientUuid: $d['client_uuid'] ?? null,
        );
    }
}
