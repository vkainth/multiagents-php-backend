<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\NearbyPlacesService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NearbyAmenitiesController extends Controller
{
    public function __construct(private NearbyPlacesService $places) {}

    public function fetch(Request $request): JsonResponse
    {
        $lat    = (float)  $request->input('lat',    0);
        $lng    = (float)  $request->input('lng',    0);
        $radius = (int)    $request->input('radius', NearbyPlacesService::DEFAULT_RADIUS);
        $slug   = (string) $request->input('slug',   '');

        if (!$lat || !$lng || abs($lat) > 90 || abs($lng) > 180) {
            return response()->json(['ok' => false, 'error' => 'invalid_coords'], 400);
        }
        $radius = max(200, min(5000, $radius));
        $slug   = preg_replace('/[^a-z0-9\-_]/', '', strtolower($slug));

        try {
            $data     = $this->places->getAll($lat, $lng, $radius, $slug);
            $hasAny   = collect($data)->contains(fn($tab) => count($tab) > 0);

            $walkScore = null;
            if (config('services.walkscore.api_key')) {
                $addr      = (string) $request->input('address', '');
                $walkScore = $this->places->getWalkScore($lat, $lng, $addr);
            }

            return response()->json([
                'ok'         => true,
                'data'       => $data,
                'has_data'   => $hasAny,
                'walk_score' => $walkScore,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('NearbyAmenities service error', [
                'lat' => $lat, 'lng' => $lng, 'radius' => $radius,
                'err' => $e->getMessage(), 'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['ok' => false, 'error' => 'service_error'], 500);
        }
    }
}
