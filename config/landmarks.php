<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Landmarks — per-territory config for "condos near {landmark}" pages
    |--------------------------------------------------------------------------
    | Each entry requires:
    |   slug       — URL-safe identifier, used in /condos-near-{slug}
    |   name       — Human-readable name shown in H1 / meta
    |   city       — Matches the city field in the Places/Listings tables
    |   lat / lng  — WGS-84 coordinates of the landmark centre-point
    |   radius_km  — Radius (km) within which listings are included (default 3)
    */

    'landmarks' => [

        // --- White Rock / South Surrey ---
        [
            'slug'      => 'white-rock-beach',
            'name'      => 'White Rock Beach',
            'city'      => 'White Rock',
            'lat'       => 49.0176,
            'lng'       => -122.8025,
            'radius_km' => 3,
        ],
        [
            'slug'      => 'morgan-creek-golf-course',
            'name'      => 'Morgan Creek Golf Course',
            'city'      => 'Surrey',
            'lat'       => 49.0633,
            'lng'       => -122.7562,
            'radius_km' => 3,
        ],
        [
            'slug'      => 'surrey-central-skytrain',
            'name'      => 'Surrey Central SkyTrain',
            'city'      => 'Surrey',
            'lat'       => 49.1883,
            'lng'       => -122.8490,
            'radius_km' => 2,
        ],
        [
            'slug'      => 'king-george-skytrain',
            'name'      => 'King George SkyTrain',
            'city'      => 'Surrey',
            'lat'       => 49.1827,
            'lng'       => -122.8454,
            'radius_km' => 2,
        ],

        // --- Langley ---
        [
            'slug'      => 'langley-city-centre',
            'name'      => 'Langley City Centre',
            'city'      => 'Langley',
            'lat'       => 49.1044,
            'lng'       => -122.6583,
            'radius_km' => 3,
        ],
        [
            'slug'      => 'willowbrook-mall',
            'name'      => 'Willowbrook Shopping Centre',
            'city'      => 'Langley',
            'lat'       => 49.1086,
            'lng'       => -122.6639,
            'radius_km' => 2,
        ],

        // --- Vancouver ---
        [
            'slug'      => 'ubc',
            'name'      => 'University of British Columbia',
            'city'      => 'Vancouver',
            'lat'       => 49.2606,
            'lng'       => -123.2460,
            'radius_km' => 3,
        ],
        [
            'slug'      => 'downtown-vancouver',
            'name'      => 'Downtown Vancouver',
            'city'      => 'Vancouver',
            'lat'       => 49.2827,
            'lng'       => -123.1207,
            'radius_km' => 2,
        ],
        [
            'slug'      => 'vancouver-city-hall',
            'name'      => 'Vancouver City Hall',
            'city'      => 'Vancouver',
            'lat'       => 49.2607,
            'lng'       => -123.1139,
            'radius_km' => 2,
        ],

        // --- Burnaby ---
        [
            'slug'      => 'metrotown',
            'name'      => 'Metropolis at Metrotown',
            'city'      => 'Burnaby',
            'lat'       => 49.2271,
            'lng'       => -122.9990,
            'radius_km' => 2,
        ],
        [
            'slug'      => 'brentwood-skytrain',
            'name'      => 'Brentwood Town Centre SkyTrain',
            'city'      => 'Burnaby',
            'lat'       => 49.2662,
            'lng'       => -123.0002,
            'radius_km' => 2,
        ],

        // --- Richmond ---
        [
            'slug'      => 'richmond-centre',
            'name'      => 'Richmond Centre Mall',
            'city'      => 'Richmond',
            'lat'       => 49.1680,
            'lng'       => -123.1375,
            'radius_km' => 2,
        ],

        // --- Coquitlam ---
        [
            'slug'      => 'coquitlam-centre',
            'name'      => 'Coquitlam Centre',
            'city'      => 'Coquitlam',
            'lat'       => 49.2832,
            'lng'       => -122.7915,
            'radius_km' => 2,
        ],

        // --- Abbotsford ---
        [
            'slug'      => 'abbotsford-centre',
            'name'      => 'Abbotsford Centre',
            'city'      => 'Abbotsford',
            'lat'       => 49.0504,
            'lng'       => -122.3090,
            'radius_km' => 3,
        ],

    ],

];
