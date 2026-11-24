<?php

declare(strict_types=1);

namespace Modules\Audit\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Audit\Domain\Models\JournalAudit;

/** @mixin JournalAudit */
final class JournalAuditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'date' => $this->created_at?->toIso8601String(),
            'event' => $this->event,
            'description' => $this->description,
            'sujet_type' => $this->subject_type ? class_basename($this->subject_type) : null,
            'sujet_uuid' => $this->subject?->uuid ?? null,
            'auteur' => $this->causer?->nom,
            'changes' => $this->attribute_changes ?? $this->properties,
        ];
    }
}
