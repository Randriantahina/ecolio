<?php

declare(strict_types=1);

namespace App\Http\Controllers\Ecole;

use App\Application\Platform\Services\EcoleSettingsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Ecole\UpdateEcoleRequest;
use App\Http\Resources\EcoleResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
