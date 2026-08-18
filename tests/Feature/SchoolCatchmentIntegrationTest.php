<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\School;
use App\Models\SchoolCatchment;
use App\Models\Buildings;

/**
 * SchoolCatchmentIntegrationTest
 *
 * Integration test for the school catchment point-in-polygon lookup.
 * Verifies that Buildings::getSchoolCatchments() returns the correct school
 * for a known South Surrey coordinate.
 *
 * Seeds its own test data (a bounding-box polygon covering the test point),
 * runs the real ST_Contains query, asserts the school identity, then cleans up.
 *
 * Skipped automatically on SQLite because SQLite has no spatial function support.
 *
 * To run (requires MySQL with schools/school_catchments tables migrated):
 *   DB_CONNECTION=mysql php artisan test tests/Feature/SchoolCatchmentIntegrationTest.php
 *
 * @group integration
 */
class SchoolCatchmentIntegrationTest extends TestCase
{
    private ?School $testSchool = null;
    private ?SchoolCatchment $testCatchment = null;

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            $this->markTestSkipped('Spatial ST_Contains test requires MySQL — skipped on SQLite.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->testCatchment) {
            $this->testCatchment->delete();
        }
        if ($this->testSchool) {
            $this->testSchool->delete();
        }
        parent::tearDown();
    }

    /**
     * Known South Surrey test coordinate: approx. 14990 24 Ave, Surrey BC.
     * The test bounding-box polygon covers lng ∈ [-122.82, -122.77] × lat ∈ [49.02, 49.04].
     * The test point (-122.7970, 49.0280) lies inside this box.
     */
    public function test_catchment_lookup_returns_correct_school_for_south_surrey_address(): void
    {
        $testLat = 49.0280;
        $testLng = -122.7970;

        $wkt = 'MULTIPOLYGON(((-122.82 49.02,-122.77 49.02,-122.77 49.04,-122.82 49.04,-122.82 49.02)))';

        $this->testSchool = School::create([
            'name'          => 'Elgin Park Secondary (Integration Test)',
            'slug'          => 'elgin-park-secondary-integration-test-' . uniqid(),
            'school_type'   => 'Secondary',
            'district_id'   => 36,
            'district_name' => 'Surrey',
            'latitude'      => 49.0285,
            'longitude'     => -122.7978,
            'is_public'     => true,
        ]);

        $this->testCatchment = SchoolCatchment::create([
            'school_id'       => $this->testSchool->id,
            'level'           => 'Secondary',
            'district_id'     => 36,
            'catchment_name'  => 'Elgin Park Secondary (Integration Test)',
            'polygon_wkt'     => $wkt,
            'polygon_geojson' => json_encode([
                'type'        => 'MultiPolygon',
                'coordinates' => [
                    [[[-122.82, 49.02], [-122.77, 49.02], [-122.77, 49.04], [-122.82, 49.04], [-122.82, 49.02]]],
                ],
            ]),
        ]);

        DB::statement(
            'UPDATE school_catchments SET polygon_geom = ST_GeomFromText(?) WHERE id = ?',
            [$wkt, $this->testCatchment->id]
        );

        $building            = new Buildings();
        $building->latitude  = $testLat;
        $building->longitude = $testLng;
        $building->slug      = 'integration-test-south-surrey-building-' . uniqid();

        Cache::forget('school_catchments_v1_' . $building->slug);

        $schools = $building->getSchoolCatchments();

        $this->assertNotEmpty($schools, 'Expected at least one catchment school for the South Surrey test coordinate.');

        $names = $schools->pluck('name')->toArray();
        $this->assertContains(
            'Elgin Park Secondary (Integration Test)',
            $names,
            'Expected the seeded test school to appear in the catchment results.'
        );

        $secondarySchools = $schools->filter(fn($s) => ($s->pivot_level ?? '') === 'Secondary');
        $this->assertNotEmpty($secondarySchools, 'Expected a Secondary-level catchment for this address.');
    }

    public function test_catchment_lookup_returns_empty_for_point_outside_all_polygons(): void
    {
        $wkt = 'MULTIPOLYGON(((-122.82 49.02,-122.77 49.02,-122.77 49.04,-122.82 49.04,-122.82 49.02)))';

        $this->testSchool = School::create([
            'name'          => 'Outside Test School (Integration Test)',
            'slug'          => 'outside-test-school-integration-' . uniqid(),
            'school_type'   => 'Elementary',
            'district_id'   => 36,
            'district_name' => 'Surrey',
            'is_public'     => true,
        ]);

        $this->testCatchment = SchoolCatchment::create([
            'school_id'   => $this->testSchool->id,
            'level'       => 'Elementary',
            'district_id' => 36,
            'polygon_wkt' => $wkt,
        ]);

        DB::statement(
            'UPDATE school_catchments SET polygon_geom = ST_GeomFromText(?) WHERE id = ?',
            [$wkt, $this->testCatchment->id]
        );

        $building            = new Buildings();
        $building->latitude  = 49.2000;
        $building->longitude = -123.0000;
        $building->slug      = 'integration-test-outside-building-' . uniqid();

        Cache::forget('school_catchments_v1_' . $building->slug);

        $schools = $building->getSchoolCatchments();

        $names = $schools->pluck('name')->toArray();
        $this->assertNotContains(
            'Outside Test School (Integration Test)',
            $names,
            'Point well outside the polygon should not match the test school.'
        );
    }
}
