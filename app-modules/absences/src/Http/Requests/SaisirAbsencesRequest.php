<?php

declare(strict_types=1);

namespace Modules\Absences\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Absences\Domain\Models\Absence;

final class SaisirAbsencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('manage', Absence::class);
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'periode' => ['sometimes', Rule::in(['journee', 'matin', 'apres_midi'])],
            'absents' => ['present', 'array', 'max:200'],
            'absents.*.eleve_uuid' => ['required', 'uuid', 'distinct'],
            'absents.*.justifiee' => ['sometimes', 'boolean'],
            'absents.*.motif' => ['nullable', 'string', 'max:255'],
            'absents.*.client_uuid' => ['nullable', 'uuid'],
        ];
    }
}
