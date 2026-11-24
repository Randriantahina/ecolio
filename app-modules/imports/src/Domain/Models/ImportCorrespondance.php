<?php

declare(strict_types=1);

namespace Modules\Imports\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Tenancy\Domain\BelongsToEcole;

final class ImportCorrespondance extends Model
{
    use BelongsToEcole;

    protected $table = 'import_correspondances';

    protected $guarded = [];
}
