<?php

declare(strict_types=1);

namespace Modules\Finance\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Child of a payment: tenancy is carried by the parent, so no scope of its own. */
final class PaiementLigne extends Model
{
    public $timestamps = false;

    protected $table = 'paiement_lignes';

    protected $guarded = [];

    public function echeance(): BelongsTo
    {
        return $this->belongsTo(Echeance::class);
    }

    public function paiement(): BelongsTo
    {
        return $this->belongsTo(Paiement::class);
    }
}
