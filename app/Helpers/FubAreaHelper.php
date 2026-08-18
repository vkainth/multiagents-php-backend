<?php

namespace App\Helpers;

class FubAreaHelper
{
    private static array $VANCOUVER = [
        'vancouver', 'burnaby', 'richmond', 'north vancouver', 'west vancouver',
        'new westminster', 'coquitlam', 'port coquitlam', 'port moody', 'pitt meadows',
    ];

    private static array $FRASER_VALLEY = [
        'surrey', 'white rock', 'langley', 'langley city', 'township of langley',
        'cloverdale', 'south surrey', 'delta', 'ladner', 'tsawwassen',
    ];

    private static array $ABBOTSFORD_CHILLIWACK = [
        'abbotsford', 'chilliwack', 'hope', 'sardis', 'mission', 'maple ridge',
    ];

    public static function areaTag(?string $city): ?string
    {
        if (!$city) {
            return null;
        }
        $normalized = strtolower(trim($city));

        foreach (self::$VANCOUVER as $match) {
            if (str_contains($normalized, $match)) {
                return 'area-van';
            }
        }
        foreach (self::$FRASER_VALLEY as $match) {
            if (str_contains($normalized, $match)) {
                return 'area-fv';
            }
        }
        foreach (self::$ABBOTSFORD_CHILLIWACK as $match) {
            if (str_contains($normalized, $match)) {
                return 'area-ac';
            }
        }
        return null;
    }

    public static function applyTag(array &$person, ?string $city): void
    {
        $tag = self::areaTag($city) ?? 'area-van';
        $existing = $person['tags'] ?? [];
        if (!in_array($tag, $existing, true)) {
            $existing[] = $tag;
        }
        $person['tags'] = $existing;
    }

    public static function saveToSession(?string $city): void
    {
        $tag = self::areaTag($city);
        if ($tag) {
            \Illuminate\Support\Facades\Session::put('fub_area_tag', $tag);
        }
        if ($city) {
            \Illuminate\Support\Facades\Session::put('fub_city', $city);
        }
    }

    public static function applyTagFromSession(array &$person): void
    {
        $tag = \Illuminate\Support\Facades\Session::get('fub_area_tag') ?? 'area-van';
        $existing = $person['tags'] ?? [];
        if (!in_array($tag, $existing, true)) {
            $existing[] = $tag;
        }
        $person['tags'] = $existing;
    }

    /**
     * Push a "Property Search" event to Follow Up Boss for the currently
     * authenticated user.  Silently no-ops when:
     *  - No user is authenticated
     *  - The user is a pixilink.com internal account
     *  - The user has not verified their phone number
     *  - The same URL was already sent within the last hour (throttle)
     *
     * @param string      $message   Short description shown in FUB activity feed (e.g. "Vancouver Apartment for sale")
     * @param string      $sourceUrl Canonical URL of the search page being visited
     * @param string|null $city      City string used to apply area tags (optional)
     */
    public static function pushSearchEvent(string $message, string $sourceUrl, ?string $city = null): void
    {
        $fubUser = \Illuminate\Support\Facades\Auth::user();
        if (!$fubUser) {
            return;
        }

        $fubEmailDomain = $fubUser->email ? strtolower(substr(strrchr($fubUser->email, '@'), 1)) : '';
        if ($fubEmailDomain === 'pixilink.com' || !$fubUser->phone_verified) {
            return;
        }

        $throttleKey = 'fub_search_' . $fubUser->id . '_' . md5($sourceUrl);
        if (\Illuminate\Support\Facades\Cache::has($throttleKey)) {
            return;
        }
        \Illuminate\Support\Facades\Cache::put($throttleKey, 1, 3600);

        \Illuminate\Support\Facades\Log::info('FUB property_search', [
            'user' => $fubUser->email,
            'url'  => $sourceUrl,
            'msg'  => $message,
        ]);

        try {
            $fubPerson = [
                'contacted'  => false,
                'firstName'  => $fubUser->first ?? '',
                'lastName'   => $fubUser->last ?? '',
                'stage'      => 'Lead',
                'source'     => 'website',
                'sourceUrl'  => $sourceUrl,
                'emails'     => [['value' => $fubUser->email ?? '']],
                'phones'     => [['value' => ($fubUser->phone_country_code ?? '') . ($fubUser->phone ?? '')]],
            ];
            if ($fubUser->followupboss_people_id) {
                $fubPerson['id'] = $fubUser->followupboss_people_id;
            }
            self::applyTag($fubPerson, $city);
            self::saveToSession($city);

            $fubPayload = [
                'person'    => $fubPerson,
                'source'    => 'bccondosandhomes.com',
                'system'    => 'website_api',
                'type'      => 'Viewed Page',
                'message'   => $message,
                'sourceUrl' => $sourceUrl,
            ];

            $fubCurl = curl_init();
            curl_setopt_array($fubCurl, [
                CURLOPT_URL            => 'https://api.followupboss.com/v1/events',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS     => json_encode($fubPayload),
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTPHEADER     => [
                    'accept: application/json',
                    'authorization: Basic ' . config('services.followupboss.api_key'),
                    'content-type: application/json',
                ],
            ]);
            $fubResponse = curl_exec($fubCurl);
            $fubError    = curl_error($fubCurl);
            curl_close($fubCurl);

            \Illuminate\Support\Facades\Log::info('FUB property_search response', [
                'user'     => $fubUser->email,
                'response' => $fubResponse,
                'error'    => $fubError,
            ]);

            $fubData = json_decode($fubResponse, true);
            if (!$fubUser->followupboss_people_id && isset($fubData['person']['id'])) {
                $fubUser->followupboss_people_id = $fubData['person']['id'];
                $fubUser->save();
            }
        } catch (\Throwable $fubEx) {
            \Illuminate\Support\Facades\Log::error('FUB property_search error: ' . $fubEx->getMessage());
        }
    }
}
