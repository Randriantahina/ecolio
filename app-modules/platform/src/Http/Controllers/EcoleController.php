<?php

declare(strict_types=1);

namespace Modules\Platform\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Platform\Application\Services\EcoleSettingsService;
use Modules\Platform\Http\Requests\UpdateEcoleRequest;
use Modules\Platform\Http\Resources\EcoleResource;

final class EcoleController extends Controller
{
    public function __construct(private readonly EcoleSettingsService $settings) {}

    public function show(Request $request): JsonResponse
    {
        // A platform super admin has no school: nothing to show, not an authorization failure.
        abort_if($request->user()->ecole_id === null, 404);

        return response()->json(['ecole' => EcoleResource::make($this->settings->voir($request->user()))->resolve()]);
    }

    public function update(UpdateEcoleRequest $request): JsonResponse
    {
        return response()->json([
            'ecole' => EcoleResource::make($this->settings->modifier($request->user(), $request->toDto()))->resolve(),
        ]);
    }
}
