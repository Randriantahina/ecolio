<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenancy\Domain\BelongsToEcole;

/** RG-R2: never edited or deleted after issue; a void is recorded on the payment, the number stays. */
final class Recu extends Model
{
    use BelongsToEcole;

    public $timestamps = false;

    protected $table = 'recus';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['emis_le' => 'datetime', 'annee' => 'integer', 'numero' => 'integer'];
    }

    public function paiement(): BelongsTo
    {
        return $this->belongsTo(Paiement::class);
    }
}
