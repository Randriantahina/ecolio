<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Navigation\MenuPrincipal;
use App\Http\Resources\UtilisateurResource;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    /** @return array<string, mixed> */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            // Lazy closures: nothing is queried for pages that never read these props.
            'auth' => fn () => [
                'utilisateur' => $request->user()
                    ? UtilisateurResource::make($request->user()->loadMissing('ecole'))->resolve()
                    : null,
            ],
            'navigation' => fn () => $request->user() ? (new MenuPrincipal)->pour($request->user()) : [],
            'flash' => fn () => [
                'succes' => $request->session()->get('succes'),
                'avertissement' => $request->session()->get('avertissement'),
            ],
        ];
    }
}
