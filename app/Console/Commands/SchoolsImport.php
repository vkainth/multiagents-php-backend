<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\School;
use App\Models\SchoolCatchment;

/**
 * SchoolsImport
 *
 * Imports BC school locations from the Ministry of Education open-data CSV
 * and (optionally) school district catchment boundary GeoJSON files into
 * the `schools` and `school_catchments` tables.
 *
 * The command is fully idempotent: re-running with the same data updates
 * existing records rather than creating duplicates. Schools are upserted
 * by (name, district_id); catchments by (school_id, level).
 *
 * Usage:
 *   php artisan schools:import                          # import all schools, no catchments
 *   php artisan schools:import --district=36            # filter to SD36 only
 *   php artisan schools:import --geojson=path/to/sd36_catchments.geojson
 *   php artisan schools:import --district=36 --fresh    # truncate before re-importing
 */
class SchoolsImport extends Command
{
    protected $signature = 'schools:import
                            {--district= : School district number to filter (e.g. 36 for Surrey SD36)}
                            {--geojson=  : Path to a GeoJSON file containing catchment boundary polygons}
                            {--fresh     : Truncate existing records before importing}';

    protected $description = 'Import BC school locations and catchment boundaries from open-data sources';

    const BC_SCHOOLS_CSV_URL = 'https://catalogue.data.gov.bc.ca/dataset/3ad10e96-d57d-4f34-b40a-6de9d1b92bbb/resource/e4ad7f08-29b2-4c94-a61b-9ca3e35c3ca3/download/schools-in-bc.csv';

    const DISTRICT_NAMES = [
        36  => 'Surrey',
        37  => 'Delta',
        38  => 'Richmond',
        39  => 'Vancouver',
        40  => 'New Westminster',
        41  => 'Burnaby',
        42  => 'Maple Ridge-Pitt Meadows',
        43  => 'Coquitlam',
        44  => 'North Vancouver',
        45  => 'West Vancouver',
        46  => 'Sunshine Coast',
        34  => 'Abbotsford',
        33  => 'Chilliwack',
        35  => 'Langley',
    ];

    public function handle(): int
    {
        $districtFilter = $this->option('district') ? (int) $this->option('district') : null;
        $geojsonPath    = $this->option('geojson');
        $fresh          = (bool) $this->option('fresh');

        if ($fresh) {
            $this->warn('[schools:import] --fresh: truncating existing records…');
            SchoolCatchment::truncate();
            School::truncate();
        }

        $this->importSchoolsCsv($districtFilter);

        if ($geojsonPath) {
            $this->importCatchmentGeoJson($geojsonPath, $districtFilter);
        }

        $this->info('[schools:import] Done.');
        return 0;
    }

    private function importSchoolsCsv(?int $districtFilter): void
    {
        $this->info('[schools:import] Fetching BC Ministry of Education schools CSV…');

        try {
            $response = Http::timeout(30)->get(self::BC_SCHOOLS_CSV_URL);
            if (! $response->successful()) {
                $this->error('  Failed to fetch CSV (HTTP ' . $response->status() . '). Skipping school import.');
                return;
            }
            $csvText = $response->body();
        } catch (\Throwable $e) {
            $this->error('  HTTP error: ' . $e->getMessage() . '. Skipping school import.');
            return;
        }

        $lines  = explode("\n", $csvText);
        $header = null;
        $count  = 0;
        $skip   = 0;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $row = str_getcsv($line);

            if ($header === null) {
                $header = array_map('strtolower', array_map('trim', $row));
                continue;
            }

            if (count($row) !== count($header)) {
                $skip++;
                continue;
            }

            $data = array_combine($header, $row);

            $districtId = (int) ($data['district_number'] ?? $data['school_district_number'] ?? 0);

            if ($districtFilter && $districtId !== $districtFilter) {
                continue;
            }

            $schoolType = $this->normaliseSchoolType($data['school_type_name'] ?? $data['facility_type'] ?? '');
            $name       = trim($data['school_name'] ?? $data['facility_name'] ?? '');

            if (empty($name)) {
                $skip++;
                continue;
            }

            $lat = isset($data['latitude'])  && is_numeric($data['latitude'])  ? (float) $data['latitude']  : null;
            $lng = isset($data['longitude']) && is_numeric($data['longitude']) ? (float) $data['longitude'] : null;

            $districtName = self::DISTRICT_NAMES[$districtId]
                ?? trim($data['district_name'] ?? $data['school_district_name'] ?? '');

            $values = [
                'address'       => trim($data['address_line1'] ?? $data['school_address'] ?? ''),
                'city'          => trim($data['city'] ?? $data['municipality'] ?? ''),
                'province'      => 'BC',
                'postal_code'   => trim($data['postal_code'] ?? ''),
                'latitude'      => $lat,
                'longitude'     => $lng,
                'school_type'   => $schoolType,
                'district_name' => $districtName,
                'facility_type' => trim($data['facility_type'] ?? ''),
                'is_public'     => true,
            ];

            $school = School::where('name', $name)
                ->where('district_id', $districtId ?: null)
                ->first();

            if ($school) {
                $school->update($values);
            } else {
                $values['name']        = $name;
                $values['slug']        = $this->makeUniqueSlug($name, $districtId);
                $values['district_id'] = $districtId ?: null;
                School::create($values);
            }

            $count++;
        }

        $this->info("  Imported/updated {$count} schools (skipped {$skip} rows).");
    }

    private function importCatchmentGeoJson(string $path, ?int $districtFilter): void
    {
        $this->info("[schools:import] Importing catchment GeoJSON from: {$path}");

        if (! file_exists($path)) {
            $this->error("  File not found: {$path}");
            return;
        }

        $raw = file_get_contents($path);
        if (! $raw) {
            $this->error('  Could not read file.');
            return;
        }

        $geojson = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($geojson['features'])) {
            $this->error('  Invalid or empty GeoJSON.');
            return;
        }

        $count = 0;
        $skip  = 0;

        foreach ($geojson['features'] as $feature) {
            $props = $feature['properties'] ?? [];
            $geom  = $feature['geometry']   ?? null;

            if (! $geom) {
                $skip++;
                continue;
            }

            $schoolName = trim(
                $props['SCHOOL_NAME'] ?? $props['school_name'] ?? $props['name'] ?? $props['NAME'] ?? ''
            );
            $level      = $this->normaliseCatchmentLevel($props['GRADE_GROUP'] ?? $props['level'] ?? $props['LEVEL'] ?? '');
            $districtId = (int) ($props['DISTRICT_NUMBER'] ?? $props['district_id'] ?? $districtFilter ?? 0);

            if (empty($schoolName)) {
                $skip++;
                continue;
            }

            $school = School::where('name', $schoolName)
                ->when($districtId, fn($q) => $q->where('district_id', $districtId))
                ->first();

            if (! $school) {
                $school = School::create([
                    'name'          => $schoolName,
                    'slug'          => $this->makeUniqueSlug($schoolName, $districtId),
                    'district_id'   => $districtId ?: null,
                    'district_name' => self::DISTRICT_NAMES[$districtId] ?? '',
                    'school_type'   => $level === 'Secondary' ? 'Secondary' : 'Elementary',
                    'is_public'     => true,
                ]);
            }

            $geojsonStr = json_encode($geom);
            $wkt        = $this->geomToWkt($geom);

            $values = [
                'district_id'    => $districtId ?: null,
                'catchment_name' => $schoolName,
                'polygon_geojson'=> $geojsonStr,
                'polygon_wkt'    => $wkt,
            ];

            $catchment = SchoolCatchment::updateOrCreate(
                ['school_id' => $school->id, 'level' => $level],
                $values
            );

            if (DB::getDriverName() === 'mysql' && $wkt) {
                DB::statement(
                    'UPDATE school_catchments SET polygon_geom = ST_GeomFromText(?) WHERE id = ?',
                    [$wkt, $catchment->id]
                );
            }

            $count++;
        }

        $this->info("  Imported/updated {$count} catchments (skipped {$skip} features).");
    }

    private function normaliseSchoolType(string $raw): string
    {
        $raw = strtolower($raw);
        if (str_contains($raw, 'secondary') || str_contains($raw, 'high')) {
            return 'Secondary';
        }
        if (str_contains($raw, 'middle') || str_contains($raw, 'junior')) {
            return 'Middle';
        }
        if (str_contains($raw, 'elementary') || str_contains($raw, 'primary')) {
            return 'Elementary';
        }
        return 'Other';
    }

    private function normaliseCatchmentLevel(string $raw): string
    {
        $raw = strtolower($raw);
        if (str_contains($raw, 'secondary') || str_contains($raw, 'high') || str_contains($raw, 'sr')) {
            return 'Secondary';
        }
        if (str_contains($raw, 'middle') || str_contains($raw, 'junior')) {
            return 'Middle';
        }
        return 'Elementary';
    }

    /**
     * makeUniqueSlug
     * Generates a deterministic base slug from name + district_id.
     * Appends a numeric suffix only if a school with a DIFFERENT name/district
     * already holds that slug (i.e., a true collision between distinct schools).
     * Re-importing the same school always produces the same slug.
     */
    private function makeUniqueSlug(string $name, int $districtId): string
    {
        $base = Str::slug($name);
        if ($districtId) {
            $base .= '-sd' . $districtId;
        }
        $slug   = $base;
        $suffix = 2;
        while (
            School::where('slug', $slug)
                ->where(function ($q) use ($name, $districtId) {
                    $q->where('name', '!=', $name)
                      ->orWhere('district_id', '!=', $districtId ?: null);
                })
                ->exists()
        ) {
            $slug = $base . '-' . $suffix++;
        }
        return $slug;
    }

    /**
     * geomToWkt
     * Convert a GeoJSON geometry object to a WKT MULTIPOLYGON string
     * suitable for ST_GeomFromText().
     */
    private function geomToWkt(array $geom): string
    {
        $type   = strtoupper($geom['type'] ?? '');
        $coords = $geom['coordinates'] ?? [];

        if ($type === 'MULTIPOLYGON') {
            $polygons = array_map(fn($poly) => $this->polygonCoordsToWkt($poly), $coords);
            return 'MULTIPOLYGON(' . implode(',', $polygons) . ')';
        }

        if ($type === 'POLYGON') {
            $inner = $this->polygonCoordsToWkt($coords);
            return 'MULTIPOLYGON(' . $inner . ')';
        }

        return '';
    }

    private function polygonCoordsToWkt(array $rings): string
    {
        $ringStrings = array_map(function ($ring) {
            $points = array_map(fn($pt) => $pt[0] . ' ' . $pt[1], $ring);
            return '(' . implode(',', $points) . ')';
        }, $rings);
        return '(' . implode(',', $ringStrings) . ')';
    }
}
