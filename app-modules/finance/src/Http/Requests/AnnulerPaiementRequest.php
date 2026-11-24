<?php

declare(strict_types=1);

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Finance\Domain\Models\Paiement;

final class AnnulerPaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('annuler', Paiement::class);
    }

    public function rules(): array
    {
        // RG-P4: no void without a reason.
        return ['motif' => ['required', 'string', 'min:3', 'max:255']];
    }
}
