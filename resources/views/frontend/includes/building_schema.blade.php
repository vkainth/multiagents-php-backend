@php
/**
 * @var \App\Models\Buildings $building
 * @var \Illuminate\Database\Eloquent\Collection $nearbyBuildings
 * @var \Illuminate\Database\Eloquent\Collection $active_listings
 * @var \Illuminate\Database\Eloquent\Collection $sold_listings
 * @var array|null $building_additional_information
 * @var array $combinedPhotoUrls
 * @var array $jsonldSchema
 */

use Illuminate\Support\Facades\Date;

// --- Helper function to safely get nested array values ---
if (!function_exists('get_nested_value')) {
    function get_nested_value($array, $keys, $default = null) {
        $current = $array;
        foreach ($keys as $key) {
            if (!isset($current[$key]) || empty($current[$key])) {
                return $default;
            }
            $current = $current[$key];
        }
        return $current;
    }
}

// --- Dynamic Date Modified Calculation ---
$dates = [];
if (isset($building->updated)) $dates[] = Date::parse($building->updated);
if (isset($active_listings) && $active_listings->isNotEmpty()) $dates[] = Date::parse($active_listings[0]->list_date);
if (isset($sold_listings) && $sold_listings->isNotEmpty()) $dates[] = Date::parse($sold_listings[0]->sold_date);
$mostRecentDate = !empty($dates) ? max($dates) : Date::now();
$pageDateModified = $mostRecentDate->toIso8601String();

// --- Array to hold all our schema parts for the @graph ---
$schemas = [];
$buildingId = route('building-detail-page', ['slug' => $building->getCanonicalSlug() ?? $building->slug]);

// --- 1. Main Schema for the Building ---
$mainSchema = [
    '@type' => ['ApartmentComplex', 'Residence', 'Place'],
    '@id' => $buildingId . '#building',
    'name' => html_entity_decode(Helper::properCasePlace($building->name) . " - " . Helper::properCasePlace($building->street_no . " " . $building->street_name . ' ' . $building->street_type)),
    'url' => $buildingId,
    'description' => trim(str_replace(["\r", "\n"], '', View::yieldContent('meta_description', 'BCCondosAndHomes'))),
];

// --- Add Rich Details to Main Schema ---
if (!empty($combinedPhotoUrls[0])) $mainSchema['image'] = $combinedPhotoUrls[0];
if (!empty($building->yearbuilt)) $mainSchema['foundingDate'] = $building->yearbuilt;
if ((float)($building->latitude ?? 0) !== 0.0 && (float)($building->longitude ?? 0) !== 0.0) {
    $mainSchema['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float)$building->latitude, 'longitude' => (float)$building->longitude];
}
// --- Add Amenities and Features ---
$amenityFeatures = [];
if (!empty($building->amenities) && $building->amenities !== 'NONE') {
    $amenities = explode(',', $building->amenities);
    foreach($amenities as $amenity) {
        $amenityFeatures[] = ['@type' => 'LocationFeatureSpecification', 'name' => trim(ucwords(strtolower($amenity))), 'value' => true];
        if (stripos($amenity, 'laundry') !== false) $amenityFeatures[] = ['@type' => 'LocationFeatureSpecification', 'name' => 'In-suite Laundry', 'value' => true];
    }
}
if (!empty($mainSchema['amenityFeature'])) {
    $mainSchema['amenityFeature'] = array_values(array_unique($amenityFeatures, SORT_REGULAR));
}

// --- Add Additional Properties ---
$additionalProperties = [];
$propMappings = [
    'Levels' => $building->levels,
    'Suites' => get_nested_value($building_additional_information, ['data', 'building', 'building_condo_info', 'suites']),
    'Status' => get_nested_value($building_additional_information, ['data', 'building', 'building_condo_info', 'status']),
    'Year Built' => $building->yearbuilt,
    'Title To Land' => $building->title_to_land,
    'Strata Plan' => $building->strata_no,
    'Construction' => $building->construction,
    'Rain Screen' => get_nested_value($building_additional_information, ['data', 'building', 'construction_info', 'rain_screen']),
    'Roof' => $building->roof,
    'Foundation' => $building->foundation,
    'Exterior Finish' => $building->exterior_finish,
];
foreach ($propMappings as $name => $value) {
    if (!empty($value)) {
        $additionalProperties[] = ['@type' => 'PropertyValue', 'name' => $name, 'value' => $value];
    }
}
if (!empty($additionalProperties)) {
    $mainSchema['additionalProperty'] = $additionalProperties;
}

// --- Active Listings as an Offer Catalog ---
if (isset($active_listings) && $active_listings->count() > 0) {
    $mainSchema['hasOfferCatalog'] = [
        '@type' => 'OfferCatalog',
        'name' => 'For Sale in ' . $building->name,
        'itemListElement' => $active_listings->map(function($listing) use ($building) {
            $apartment = ['@type' => 'Apartment', 'name' => Helper::properCasePlace(implode(' ', array_filter([$listing->suite_no ?? '', $building->street_no ?? '', strtolower($building->street_dir ?? ''), strtolower($building->street_name ?? ''), strtolower($building->street_type ?? '')])))];
            if (!empty($listing->yearbuilt)) $apartment['yearBuilt'] = $listing->yearbuilt;
            return ['@type' => 'Offer', 'url' => route('listing-detail-page2', ['slug' => $listing->slug]), 'price' => $listing->listprice_2, 'priceCurrency' => 'CAD', 'itemOffered' => $apartment];
        })->toArray(),
    ];
}
$schemas[] = $mainSchema;

// --- 2. WebPage Schema ---
$schemas[] = [
    '@type' => 'WebPage',
    '@id' => $buildingId,
    'datePublished' => Date::parse($building->inserted ?? 'now')->toIso8601String(),
    'dateModified' => $pageDateModified,
    'mainEntity' => ['@id' => $buildingId . '#building'],
];

// --- 3. Breadcrumbs Schema ---
if (!empty($jsonldSchema['BreadcrumbList'])) {
     foreach ($jsonldSchema['BreadcrumbList'] as $_jsonldSchema) {
        $schemas[] = ['@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn($crumb, $index) => ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $crumb['text'], 'item' => $crumb['url']], $_jsonldSchema, array_keys($_jsonldSchema))];
     }
}

// --- 4. FAQ Page Schema ---
$faqs = [];
if (!empty($building->yearbuilt)) $faqs[] = ['@type' => 'Question', 'name' => "What year was {$building->name} built?", 'acceptedAnswer' => ['@type' => 'Answer', 'text' => "{$building->name} was constructed in {$building->yearbuilt}."]];
if (!empty($building->units_in_development)) $faqs[] = ['@type' => 'Question', 'name' => "How many units are in {$building->name}?", 'acceptedAnswer' => ['@type' => 'Answer', 'text' => "There are {$building->units_in_development} units in this building."]];
// Merge market-data FAQs from building.blade.php (active listing count, avg sale price, etc.)
if (!empty($_faqItems)) {
    foreach ($_faqItems as $_fi) {
        $faqs[] = ['@type' => 'Question', 'name' => $_fi['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $_fi['a']]];
    }
}
if (!empty($faqs)) $schemas[] = ['@type' => 'FAQPage', 'mainEntity' => $faqs];

// --- 5. ImageGallery Schema ---
if (!empty($combinedPhotoUrls)) {
    $schemas[] = ['@type' => 'ImageGallery', 'name' => 'Photos of ' . $building->name, 'image' => array_map(fn($url) => ['@type' => 'ImageObject', 'contentUrl' => $url], $combinedPhotoUrls)];
}

\Debugbar::info($schemas);
@endphp
<script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": {!! json_encode($schemas, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    }
</script>
