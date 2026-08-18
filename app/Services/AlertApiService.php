<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AlertApiService
{
    private const BASE_URL = 'https://admin.bccondosandhomes.com/api/v1/alerts';

    /**
     * Create a subscription via the Alert API.
     * Returns the subscription ID on success, null on failure.
     * This is a synchronous call — use dispatch() to run it after the response.
     */
    public static function create(array $payload): ?string
    {
        $apiKey = config('bcch.alert_api_key');
        if (!$apiKey) {
            return null;
        }

        try {
            $ch = curl_init(self::BASE_URL);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'X-Api-Key: ' . $apiKey,
                ],
                CURLOPT_TIMEOUT => 10,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                return $data['id'] ?? null;
            }

            Log::warning('AlertApiService: non-2xx response', [
                'httpCode' => $httpCode,
                'response' => substr((string) $response, 0, 500),
                'email'    => $payload['email'] ?? null,
                'name'     => $payload['name'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('AlertApiService create failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Dispatch the API call after the HTTP response is sent (non-blocking).
     */
    public static function dispatch(array $payload): void
    {
        if (!config('bcch.alert_api_key')) {
            return;
        }
        try {
            dispatch(static function () use ($payload) {
                static::create($payload);
            })->afterResponse();
        } catch (\Exception $e) {
            Log::error('AlertApiService dispatch failed: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Payload builders
    // -------------------------------------------------------------------------

    /**
     * Building follow alert.
     * Default alertTypes: new listing + sold events for that building.
     */
    public static function payloadForBuilding(
        string $email,
        string $buildingName,
        string $city = '',
        array  $alertTypes = ['new_listing', 'sold']
    ): array {
        $payload = [
            'email'               => $email,
            'name'                => 'Building Alert: ' . $buildingName,
            'buildingName'        => $buildingName,
            'alertTypes'          => $alertTypes,
            'notificationChannel' => 'email',
        ];
        if ($city) {
            $payload['city'] = $city;
        }
        return $payload;
    }

    /**
     * Specific listing watch (price drop and/or sold).
     * The API has no listingId field, so we scope by building + city.
     */
    public static function payloadForListingWatch(
        string $email,
        string $address,
        string $city,
        bool   $watchPriceDrop,
        bool   $watchSold,
        string $buildingName = ''
    ): array {
        $alertTypes = [];
        if ($watchPriceDrop) $alertTypes[] = 'price_drop';
        if ($watchSold)      $alertTypes[] = 'sold';
        if (empty($alertTypes)) $alertTypes = ['price_drop'];

        $payload = [
            'email'               => $email,
            'name'                => 'Watch: ' . $address . ($city ? ', ' . $city : ''),
            'alertTypes'          => $alertTypes,
            'notificationChannel' => 'email',
        ];
        if ($buildingName) $payload['buildingName'] = $buildingName;
        if ($city)         $payload['city']         = $city;
        return $payload;
    }

    /**
     * Search-based alert (city / neighbourhood / beds / price).
     * Reads from the JSON blob stored in saved_searches.data.
     */
    public static function payloadForSavedSearch(string $email, string $searchName, array $data): array
    {
        $alertTypes = [];
        if (!empty($data['just_listed_alert'])) $alertTypes[] = 'new_listing';
        if (!empty($data['just_sold_alert']))   $alertTypes[] = 'sold';
        if (!empty($data['price_drop_alert']))  $alertTypes[] = 'price_drop';
        if (empty($alertTypes))                 $alertTypes   = ['new_listing'];

        $payload = [
            'email'               => $email,
            'name'                => $searchName,
            'alertTypes'          => $alertTypes,
            'notificationChannel' => 'email',
        ];

        if (!empty($data['city']))          $payload['city']          = $data['city'];
        if (!empty($data['neighborhood']))  $payload['neighborhood']  = $data['neighborhood'];
        if (!empty($data['subarea']))       $payload['neighborhood']  = $data['subarea'];
        if (!empty($data['type']))          $payload['propertyTypes'] = [$data['type']];
        if (!empty($data['min_beds']))      $payload['bedsMin']       = (int) $data['min_beds'];
        if (!empty($data['max_beds']))      $payload['bedsMax']       = (int) $data['max_beds'];
        if (!empty($data['min_price']))     $payload['priceMin']      = (int) $data['min_price'];
        if (!empty($data['max_price']))     $payload['priceMax']      = (int) $data['max_price'];
        if (!empty($data['building_name'])) $payload['buildingName']  = $data['building_name'];

        return $payload;
    }
}
