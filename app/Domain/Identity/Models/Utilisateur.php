<?php

declare(strict_types=1);

namespace App\Domain\Identity\Models;

use App\Domain\Identity\Enums\Role;
use App\Domain\Platform\Models\Ecole;
use Database\Factories\UtilisateurFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use InvalidArgumentException;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

/**
 * Deliberately NOT using BelongsToEcole: login must find the account before any school is resolved,
 * and super admins have no school. Every school-side listing filters on ecole_id explicitly in its repository.
 */
final class Utilisateur extends Authenticatable implements FilamentUser, HasName, HasTenants
{
    use HasApiTokens, HasFactory, HasRoles, HasUuids, LogsActivity, Notifiable;

    public const MAX_ECHECS = 5;

    public const VERROUILLAGE_MINUTES = 15;

    protected $table = 'utilisateurs';

    protected $guarded = [];

    protected $hidden = ['mot_de_passe'];

    protected static function booted(): void
    {
        self::saving(function (self $user): void {
            if (blank($user->email) && blank($user->telephone)) {
                throw new InvalidArgumentException('A user needs an e-mail or a phone number.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'mot_de_passe' => 'hashed',
            'actif' => 'boolean',
            'verrouille_jusqua' => 'datetime',
            'derniere_connexion' => 'datetime',
        ];
    }

    /** Whitelist, never logAll: the password hash and login bookkeeping must stay out of the trail. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nom', 'email', 'telephone', 'actif'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('utilisateurs');
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    /** The password column is French per the spec; Laravel's guards need to be told. */
    public function getAuthPasswordName(): string
    {
        return 'mot_de_passe';
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class);
    }

    /** Staff of a school only; parents get their own portal in phase 5 and super admins the back-office panel. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->actif
            && $this->ecole_id !== null
            && $this->hasAnyRole([Role::Directeur->value, Role::Secretaire->value, Role::Enseignant->value]);
    }

    /** @return Collection<int, Ecole> */
    public function getTenants(Panel $panel): Collection
    {
        return Ecole::query()->whereKey($this->ecole_id)->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->ecole_id !== null && $tenant instanceof Ecole && $tenant->getKey() === $this->ecole_id;
    }

    public function getFilamentName(): string
    {
        return $this->nom;
    }

    public function estSuperAdmin(): bool
    {
        return $this->ecole_id === null;
    }

    public function aRole(Role $role): bool
    {
        return $this->hasRole($role->value);
    }

    public function estVerrouille(): bool
    {
        return $this->verrouille_jusqua !== null && $this->verrouille_jusqua->isFuture();
    }

    public function enregistrerEchec(): void
    {
        $this->tentatives_echouees = (int) $this->tentatives_echouees + 1;

        if ($this->tentatives_echouees >= self::MAX_ECHECS) {
            $this->verrouille_jusqua = now()->addMinutes(self::VERROUILLAGE_MINUTES);
        }
    }

    public function reinitialiserEchecs(): void
    {
        $this->tentatives_echouees = 0;
        $this->verrouille_jusqua = null;
    }

    protected static function newFactory(): UtilisateurFactory
    {
        return UtilisateurFactory::new();
    }
}
