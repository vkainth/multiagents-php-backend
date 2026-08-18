<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AlertWebhookService
{
    /**
     * Dispatch an alert lifecycle webhook with 3x exponential-backoff retry.
     *
     * @param string $event  subscription.created | subscription.reactivated | subscription.deleted
     * @param string $type   search | building
     * @param array  $record Record data (toArray() or plain array)
     */
    public static function dispatch(string $event, string $type, array $record): void
    {
        $url    = config('bcch.alert_webhook_url');
        $secret = config('bcch.alert_webhook_secret');

        if (!$url || !$secret) {
            return;
        }

        try {
            dispatch(static function () use ($url, $secret, $event, $type, $record) {
                $subscription = static::buildSubscriptionPayload($type, $record);

                $payload = json_encode([
                    'event'        => $event,
                    'subscription' => $subscription,
                ]);

                $attempt = 0;
                while ($attempt < 3) {
                    $attempt++;
                    $ch = curl_init($url);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_POSTFIELDS     => $payload,
                        CURLOPT_HTTPHEADER     => [
                            'Content-Type: application/json',
                            'X-Webhook-Secret: ' . $secret,
                        ],
                        CURLOPT_TIMEOUT => 10,
                    ]);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($httpCode >= 200 && $httpCode < 300) {
                        break;
                    }

                    Log::warning('AlertWebhookService: non-2xx response', [
                        'attempt'  => $attempt,
                        'httpCode' => $httpCode,
                        'response' => substr((string) $response, 0, 500),
                        'url'      => $url,
                        'event'    => $event,
                        'type'     => $type,
                    ]);

                    if ($attempt < 3) {
                        sleep((int) pow(2, $attempt));
                    }
                }
            })->afterResponse();
        } catch (\Exception $e) {
            Log::error('AlertWebhookService dispatch failed: ' . $e->getMessage());
        }
    }

    /**
     * Build the `subscription` object in the shape the admin system expects.
     */
    private static function buildSubscriptionPayload(string $type, array $record): array
    {
        $base = [
            'id'         => $record['id'] ?? null,
            'type'       => $type,
            'email'      => $record['email'] ?? null,
            'name'       => $record['name'] ?? null,
            'user_id'    => $record['userid'] ?? null,
            'created_at' => isset($record['created_at'])
                ? \Carbon\Carbon::parse($record['created_at'])->toIso8601String()
                : null,
        ];

        if ($type === 'building') {
            return array_merge($base, [
                'building_slug'     => $record['building_slug'] ?? null,
                'building_name'     => $record['building_name'] ?? null,
                'building_strata_no' => $record['strata_no'] ?? null,
                'city'              => $record['city'] ?? null,
                'watch_new'         => true,
                'watch_price_drop'  => false,
                'watch_sold'        => false,
            ]);
        }

        if ($type === 'search') {
            return array_merge($base, [
                'search_name'      => $record['search_name'] ?? null,
                'watch_new'        => (bool) ($record['just_listed_alert'] ?? true),
                'watch_price_drop' => false,
                'watch_sold'       => (bool) ($record['just_sold_alert'] ?? false),
            ]);
        }

        // Listing watch / fallback
        return array_merge($base, [
            'watch_new'        => false,
            'watch_price_drop' => (bool) ($record['watch_price_drop'] ?? false),
            'watch_sold'       => (bool) ($record['watch_sold'] ?? false),
        ]);
    }
}
