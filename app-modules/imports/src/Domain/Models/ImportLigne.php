<?php

declare(strict_types=1);

namespace Modules\Imports\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Imports\Domain\Enums\StatutLigneImport;
use Modules\Tenancy\Domain\BelongsToEcole;

final class ImportLigne extends Model
{
    use BelongsToEcole;

    protected $table = 'import_lignes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'donnees' => 'array', 'erreurs' => 'array', 'avant' => 'array',
            'statut' => StatutLigneImport::class, 'confirmee' => 'boolean',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
