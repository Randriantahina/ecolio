<?php

declare(strict_types=1);

namespace App\Domain\Eleves\Models;

use App\Domain\Scolarite\Models\AnneeScolaire;
use App\Domain\Scolarite\Models\Classe;
use App\Domain\Tenancy\BelongsToEcole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Inscription extends Model
{
    use BelongsToEcole;

    protected $table = 'inscriptions';

    protected $fillable = ['date_inscription', 'type', 'est_redoublant', 'statut'];

    protected $attributes = ['statut' => 'active', 'type' => 'nouvelle', 'est_redoublant' => false];

    protected function casts(): array
    {
        return ['date_inscription' => 'date', 'est_redoublant' => 'boolean'];
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }
}
