<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $preference = $request->user()->preference;

        return response()->json($preference);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'platforms'   => 'required|array',
            'platforms.*' => 'in:tiktok,facebook',
        ]);

        $preference = UserPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            ['platforms' => $validated['platforms']]
        );

        return response()->json($preference);
    }
}
