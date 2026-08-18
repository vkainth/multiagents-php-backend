<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\School;
use App\Models\SchoolCatchment;
use Illuminate\Support\Str;

/**
 * Seeds public schools for South Surrey / White Rock / Cloverdale (SD 36).
 * Catchment polygons are rectangular approximations; replace with official
 * BC Ministry polygon data when available.
 */
class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        $schools = [
            // ── South Surrey / White Rock — Elementary ──────────────────────
            [
                'name'        => 'Bayridge Elementary',
                'address'     => '15441 26 Ave',
                'city'        => 'Surrey',
                'postal_code' => 'V4P 2Y8',
                'latitude'    => 49.0618,
                'longitude'   => -122.8292,
                'school_type' => 'Elementary',
                'grades'      => 'K–7',
                'dLat'        => 0.014,
                'dLng'        => 0.018,
            ],
            [
                'name'        => 'Dogwood Elementary',
                'address'     => '14750 16 Ave',
                'city'        => 'Surrey',
                'postal_code' => 'V4A 1T2',
                'latitude'    => 49.0500,
                'longitude'   => -122.8200,
                'school_type' => 'Elementary',
                'grades'      => 'K–7',
                'dLat'        => 0.013,
                'dLng'        => 0.017,
            ],
            [
                'name'        => 'White Rock Elementary',
                'address'     => '1273 Fir St',
                'city'        => 'White Rock',
                'postal_code' => 'V4B 4A9',
                'latitude'    => 49.0254,
                'longitude'   => -122.8017,
                'school_type' => 'Elementary',
                'grades'      => 'K–7',
                'dLat'        => 0.014,
                'dLng'        => 0.018,
            ],
            [
                'name'        => 'Pacific Heights Elementary',
                'address'     => '13540 Blackburn Rd',
                'city'        => 'White Rock',
                'postal_code' => 'V4B 3G8',
                'latitude'    => 49.0338,
                'longitude'   => -122.7950,
                'school_type' => 'Elementary',
                'grades'      => 'K–7',
                'dLat'        => 0.014,
                'dLng'        => 0.018,
            ],
            [
                'name'        => 'South Ridge Elementary',
                'address'     => '2689 168 St',
                'city'        => 'Surrey',
                'postal_code' => 'V3S 4C3',
                'latitude'    => 49.0338,
                'longitude'   => -122.8415,
                'school_type' => 'Elementary',
                'grades'      => 'K–7',
                'dLat'        => 0.013,
                'dLng'        => 0.016,
            ],
            [
                'name'        => 'Laronde Elementary',
                'address'     => '1680 152 St',
                'city'        => 'Surrey',
                'postal_code' => 'V4A 4N2',
                'latitude'    => 49.0418,
                'longitude'   => -122.8450,
                'school_type' => 'Elementary',
                'grades'      => 'K–7',
                'dLat'        => 0.013,
                'dLng'        => 0.017,
            ],
            [
                'name'        => 'Jessie Lee Elementary',
                'address'     => '1555 164 St',
                'city'        => 'Surrey',
                'postal_code' => 'V3Z 0C4',
                'latitude'    => 49.0380,
                'longitude'   => -122.8483,
                'school_type' => 'Elementary',
                'grades'      => 'K–7',
                'dLat'        => 0.013,
                'dLng'        => 0.016,
            ],
            [
                'name'        => 'Sunrise Ridge Elementary',
                'address'     => '1901 165 St',
                'city'        => 'Surrey',
                'postal_code' => 'V3Z 0R4',
                'latitude'    => 49.0300,
                'longitude'   => -122.8451,
                'school_type' => 'Elementary',
                'grades'      => 'K–7',
                'dLat'        => 0.013,
                'dLng'        => 0.016,
            ],
            [
                'name'        => 'Bay Elementary',
                'address'     => '15000 Buena Vista Ave',
                'city'        => 'White Rock',
                'postal_code' => 'V4B 1Y3',
                'latitude'    => 49.0296,
                'longitude'   => -122.8154,
                'school_type' => 'Elementary',
                'grades'      => 'K–7',
                'dLat'        => 0.014,
                'dLng'        => 0.018,
            ],
            [
                'name'        => 'Morgan Elementary',
                'address'     => '2780 165A St',
                'city'        => 'Surrey',
                'postal_code' => 'V3Z 0M7',
                'latitude'    => 49.0320,
                'longitude'   => -122.8390,
                'school_type' => 'Elementary',
                'grades'      => 'K–7',
                'dLat'        => 0.013,
                'dLng'        => 0.016,
            ],
            // ── South Surrey / White Rock — Secondary ───────────────────────
            [
                'name'        => 'Semiahmoo Secondary',
                'address'     => '1785 148 St',
                'city'        => 'Surrey',
                'postal_code' => 'V4A 4T7',
                'latitude'    => 49.0545,
                'longitude'   => -122.8017,
                'school_type' => 'Secondary',
                'grades'      => '8–12',
                'dLat'        => 0.038,
                'dLng'        => 0.048,
            ],
            [
                'name'        => 'Earl Marriott Secondary',
                'address'     => '15751 16 Ave',
                'city'        => 'Surrey',
                'postal_code' => 'V4A 1T9',
                'latitude'    => 49.0214,
                'longitude'   => -122.7815,
                'school_type' => 'Secondary',
                'grades'      => '8–12',
                'dLat'        => 0.038,
                'dLng'        => 0.048,
            ],
            [
                'name'        => 'Elgin Park Secondary',
                'address'     => '13484 24 Ave',
                'city'        => 'Surrey',
                'postal_code' => 'V4P 1T9',
                'latitude'    => 49.0538,
                'longitude'   => -122.8497,
                'school_type' => 'Secondary',
                'grades'      => '8–12',
                'dLat'        => 0.038,
                'dLng'        => 0.048,
            ],
            // ── Cloverdale — Elementary ─────────────────────────────────────
            [
                'name'        => 'Cloverdale Elementary',
                'address'     => '5840 176 St',
                'city'        => 'Surrey',
                'postal_code' => 'V3S 4G6',
                'latitude'    => 49.0840,
                'longitude'   => -122.7298,
                'school_type' => 'Elementary',
                'grades'      => 'K–7',
                'dLat'        => 0.013,
                'dLng'        => 0.016,
            ],
            [
                'name'        => 'Forsyth Road Elementary',
                'address'     => '18787 64 Ave',
                'city'        => 'Surrey',
                'postal_code' => 'V3S 8G1',
                'latitude'    => 49.1052,
                'longitude'   => -122.7353,
                'school_type' => 'Elementary',
                'grades'      => 'K–7',
                'dLat'        => 0.013,
                'dLng'        => 0.016,
            ],
            // ── Cloverdale — Secondary ───────────────────────────────────────
            [
                'name'        => 'Lord Tweedsmuir Secondary',
                'address'     => '6151 132 St',
                'city'        => 'Surrey',
                'postal_code' => 'V3X 1K3',
                'latitude'    => 49.1232,
                'longitude'   => -122.7560,
                'school_type' => 'Secondary',
                'grades'      => '8–12',
                'dLat'        => 0.040,
                'dLng'        => 0.050,
            ],
        ];

        foreach ($schools as $row) {
            $dLat = $row['dLat'];
            $dLng = $row['dLng'];
            $lat  = $row['latitude'];
            $lng  = $row['longitude'];

            $polygon = $this->makeRectPolygon($lat, $lng, $dLat, $dLng);

            $school = School::firstOrCreate(
                ['slug' => Str::slug($row['name'])],
                [
                    'name'          => $row['name'],
                    'address'       => $row['address'],
                    'city'          => $row['city'],
                    'province'      => 'BC',
                    'postal_code'   => $row['postal_code'],
                    'latitude'      => $lat,
                    'longitude'     => $lng,
                    'school_type'   => $row['school_type'],
                    'district_name' => 'Surrey School District No. 36',
                    'district_id'   => 36,
                    'facility_type' => $row['grades'] ?? null,
                    'is_public'     => true,
                ]
            );

            SchoolCatchment::firstOrCreate(
                ['school_id' => $school->id, 'level' => $row['school_type']],
                [
                    'district_id'    => 36,
                    'catchment_name' => $row['name'] . ' Catchment',
                    'polygon_geojson'=> json_encode($polygon),
                    'polygon_wkt'    => $this->polygonToWkt($polygon),
                ]
            );
        }
    }

    private function makeRectPolygon(float $lat, float $lng, float $dLat, float $dLng): array
    {
        $minLat = $lat - $dLat;
        $maxLat = $lat + $dLat;
        $minLng = $lng - $dLng;
        $maxLng = $lng + $dLng;

        return [
            'type'        => 'Polygon',
            'coordinates' => [[
                [$minLng, $minLat],
                [$maxLng, $minLat],
                [$maxLng, $maxLat],
                [$minLng, $maxLat],
                [$minLng, $minLat],
            ]],
        ];
    }

    private function polygonToWkt(array $geojson): string
    {
        $coords = $geojson['coordinates'][0];
        $pairs  = array_map(fn($c) => "{$c[0]} {$c[1]}", $coords);
        return 'POLYGON((' . implode(', ', $pairs) . '))';
    }
}
