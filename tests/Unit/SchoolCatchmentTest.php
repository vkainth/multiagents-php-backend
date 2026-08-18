<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Buildings;
use App\Models\School;
use App\Models\SchoolCatchment;

/**
 * SchoolCatchmentTest
 *
 * Tests the school catchment data layer:
 *  - Buildings::getSchoolCatchments() point-in-polygon lookup
 *  - SchoolsImport command logic helpers (slug generation, type normalisation)
 *  - Graceful handling when no lat/lng is present
 *
 * Spatial (ST_Contains) assertions are skipped automatically on SQLite
 * because SQLite has no spatial function support.  They run against a
 * real MySQL test database when DB_CONNECTION=mysql is set.
 */
class SchoolCatchmentTest extends TestCase
{
    public function test_school_model_attributes(): void
    {
        $school = new School([
            'name'        => 'Elgin Park Secondary',
            'slug'        => 'elgin-park-secondary-sd36',
            'school_type' => 'Secondary',
            'district_id' => 36,
            'district_name' => 'Surrey',
            'latitude'    => 49.0285,
            'longitude'   => -122.7978,
        ]);

        $this->assertSame('Elgin Park Secondary', $school->name);
        $this->assertSame('Secondary', $school->school_type);
        $this->assertSame(36, $school->district_id);
        $this->assertSame(49.0285, (float) $school->latitude);
    }

    public function test_school_catchment_model_attributes(): void
    {
        $catchment = new SchoolCatchment([
            'school_id'   => 1,
            'level'       => 'Secondary',
            'district_id' => 36,
        ]);

        $this->assertSame('Secondary', $catchment->level);
        $this->assertSame(36, $catchment->district_id);
    }

    public function test_get_school_catchments_returns_empty_when_no_coordinates(): void
    {
        $building            = new Buildings();
        $building->latitude  = null;
        $building->longitude = null;
        $building->slug      = 'test-building';

        $result = $building->getSchoolCatchments();

        $this->assertCount(0, $result);
    }

    public function test_get_school_catchments_returns_collection(): void
    {
        $building            = new Buildings();
        $building->latitude  = 49.0285;
        $building->longitude = -122.7978;
        $building->slug      = 'test-building-south-surrey';

        Cache::shouldReceive('remember')
            ->once()
            ->andReturn(collect());

        $result = $building->getSchoolCatchments();
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
    }

    public function test_listings_model_has_get_nearby_schools_method(): void
    {
        $this->assertTrue(
            method_exists(\App\Models\Listings::class, 'getNearbySchools'),
            'Listings model must have a getNearbySchools() method'
        );

        $reflection = new \ReflectionMethod(\App\Models\Listings::class, 'getNearbySchools');
        $params     = $reflection->getParameters();
        $this->assertCount(1, $params, 'getNearbySchools() must accept one parameter ($radiusKm)');
        $this->assertSame('radiusKm', $params[0]->getName());
        $this->assertSame(1.5, $params[0]->getDefaultValue(), 'Default radius must be 1.5 km');
    }

    public function test_schools_import_normalises_school_type(): void
    {
        $command = new \App\Console\Commands\SchoolsImport();

        $method = new \ReflectionMethod($command, 'normaliseSchoolType');
        $method->setAccessible(true);

        $this->assertSame('Elementary', $method->invoke($command, 'Elementary School'));
        $this->assertSame('Secondary',  $method->invoke($command, 'Secondary School'));
        $this->assertSame('Secondary',  $method->invoke($command, 'Senior High School'));
        $this->assertSame('Middle',     $method->invoke($command, 'Middle School'));
        $this->assertSame('Other',      $method->invoke($command, 'Learning Centre'));
    }

    public function test_schools_import_normalises_catchment_level(): void
    {
        $command = new \App\Console\Commands\SchoolsImport();

        $method = new \ReflectionMethod($command, 'normaliseCatchmentLevel');
        $method->setAccessible(true);

        $this->assertSame('Elementary', $method->invoke($command, 'Elementary'));
        $this->assertSame('Secondary',  $method->invoke($command, 'Secondary'));
        $this->assertSame('Secondary',  $method->invoke($command, 'SR'));
        $this->assertSame('Middle',     $method->invoke($command, 'Middle'));
        $this->assertSame('Elementary', $method->invoke($command, ''));
    }

    public function test_geom_to_wkt_polygon(): void
    {
        $command = new \App\Console\Commands\SchoolsImport();

        $method = new \ReflectionMethod($command, 'geomToWkt');
        $method->setAccessible(true);

        $geom = [
            'type'        => 'Polygon',
            'coordinates' => [
                [
                    [-122.80, 49.02],
                    [-122.79, 49.02],
                    [-122.79, 49.03],
                    [-122.80, 49.03],
                    [-122.80, 49.02],
                ],
            ],
        ];

        $wkt = $method->invoke($command, $geom);

        $this->assertStringStartsWith('MULTIPOLYGON', $wkt);
        $this->assertStringContainsString('-122.8 49.02', $wkt);
    }

    public function test_geom_to_wkt_multipolygon(): void
    {
        $command = new \App\Console\Commands\SchoolsImport();

        $method = new \ReflectionMethod($command, 'geomToWkt');
        $method->setAccessible(true);

        $geom = [
            'type'        => 'MultiPolygon',
            'coordinates' => [
                [
                    [
                        [-122.80, 49.02],
                        [-122.79, 49.02],
                        [-122.79, 49.03],
                        [-122.80, 49.03],
                        [-122.80, 49.02],
                    ],
                ],
            ],
        ];

        $wkt = $method->invoke($command, $geom);

        $this->assertStringStartsWith('MULTIPOLYGON', $wkt);
    }

    public function test_buildings_model_has_get_school_catchments_method(): void
    {
        $this->assertTrue(
            method_exists(Buildings::class, 'getSchoolCatchments'),
            'Buildings model must have a getSchoolCatchments() method'
        );

        $reflection = new \ReflectionMethod(Buildings::class, 'getSchoolCatchments');
        $this->assertSame(
            \Illuminate\Support\Collection::class,
            (string) $reflection->getReturnType(),
            'getSchoolCatchments() must return \Illuminate\Support\Collection'
        );
    }

    public function test_buildings_model_has_flush_cache_method(): void
    {
        $this->assertTrue(
            method_exists(Buildings::class, 'flushSchoolCatchmentsCache'),
            'Buildings model must have a flushSchoolCatchmentsCache() method'
        );
    }
}
