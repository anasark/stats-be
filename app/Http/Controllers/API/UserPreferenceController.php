<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

class UserPreferenceController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/preferences",
     *     summary="Get the authenticated user's platform preferences",
     *     tags={"Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User preferences",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="platforms", type="array", @OA\Items(type="string", enum={"tiktok","facebook"}))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(Request $request): JsonResponse
    {
        $preference = $request->user()->preference;

        return response()->json($preference);
    }

    /**
     * @OA\Put(
     *     path="/api/preferences",
     *     summary="Update the authenticated user's platform preferences",
     *     tags={"Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"platforms"},
     *             @OA\Property(
     *                 property="platforms",
     *                 type="array",
     *                 @OA\Items(type="string", enum={"tiktok","facebook"}),
     *                 example={"tiktok","facebook"}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preferences updated",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="user_id", type="integer"),
     *             @OA\Property(property="platforms", type="array", @OA\Items(type="string", enum={"tiktok","facebook"}))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
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
