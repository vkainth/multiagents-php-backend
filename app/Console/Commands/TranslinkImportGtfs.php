<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\TransitStop;

/**
 * Imports TransLink GTFS stop data into the transit_stops table.
 *
 * Data source: https://www.translink.ca/about-us/doing-business-with-translink/app-developer-resources/gtfs
 * Public feed URL: https://gtfs.translink.ca/v2/gtfsstatic (zip file, no auth needed)
 *
 * Reads stops.txt, trips.txt, stop_times.txt, and routes.txt to build
 * a map of stop_id → route short names, then upserts into transit_stops.
 */
class TranslinkImportGtfs extends Command
{
    protected $signature   = 'translink:import-gtfs {--force : Re-import even if data already exists}';
    protected $description = 'Import TransLink GTFS stop data (stops + route numbers) into transit_stops table';

    const GTFS_URL = 'https://gtfs.translink.ca/v2/gtfsstatic';

    public function handle(): int
    {
        $count = TransitStop::count();
        if ($count > 0 && !$this->option('force')) {
            $this->info("transit_stops table already has {$count} rows. Use --force to re-import.");
            return self::SUCCESS;
        }

        $this->info('Downloading TransLink GTFS static feed…');
        $tmpZip = sys_get_temp_dir() . '/translink_gtfs_' . time() . '.zip';

        try {
            $resp = Http::timeout(60)->withOptions(['sink' => $tmpZip])->get(self::GTFS_URL);
            if (!$resp->successful()) {
                $this->error('Failed to download GTFS feed (HTTP ' . $resp->status() . ')');
                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error('Download error: ' . $e->getMessage());
            return self::FAILURE;
        }

        $tmpDir = sys_get_temp_dir() . '/translink_gtfs_' . time();
        mkdir($tmpDir);

        try {
            $zip = new \ZipArchive();
            if ($zip->open($tmpZip) !== true) {
                $this->error('Could not open GTFS zip file.');
                return self::FAILURE;
            }
            $zip->extractTo($tmpDir);
            $zip->close();
        } catch (\Throwable $e) {
            $this->error('Unzip error: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            @unlink($tmpZip);
        }

        $this->info('Parsing GTFS files…');

        $stops   = $this->parseCsv($tmpDir . '/stops.txt');
        $routes  = $this->parseCsv($tmpDir . '/routes.txt');
        $trips   = $this->parseCsv($tmpDir . '/trips.txt');
        $stTimes = $this->parseCsv($tmpDir . '/stop_times.txt');

        if (empty($stops)) {
            $this->error('stops.txt is empty or could not be parsed.');
            return self::FAILURE;
        }

        // route_id → short_name
        $routeNames = [];
        foreach ($routes as $r) {
            $id   = $r['route_id']         ?? null;
            $name = $r['route_short_name'] ?? null;
            if ($id && $name) {
                $routeNames[$id] = $name;
            }
        }

        // trip_id → route_id
        $tripRoute = [];
        foreach ($trips as $t) {
            $tid = $t['trip_id']   ?? null;
            $rid = $t['route_id']  ?? null;
            if ($tid && $rid) {
                $tripRoute[$tid] = $rid;
            }
        }

        // stop_id → set of route short names
        $stopRoutes = [];
        foreach ($stTimes as $st) {
            $sid = $st['stop_id']  ?? null;
            $tid = $st['trip_id']  ?? null;
            if (!$sid || !$tid) continue;
            $rid = $tripRoute[$tid] ?? null;
            if (!$rid) continue;
            $rname = $routeNames[$rid] ?? null;
            if (!$rname) continue;
            $stopRoutes[$sid][$rname] = true;
        }

        $this->info('Building stop records…');

        TransitStop::truncate();

        $isMysql = DB::getDriverName() === 'mysql';
        $chunks  = array_chunk($stops, 500);
        $total   = 0;

        foreach ($chunks as $chunk) {
            $rows = [];
            foreach ($chunk as $s) {
                $sid = $s['stop_id']   ?? null;
                $lat = isset($s['stop_lat']) ? (float)$s['stop_lat'] : null;
                $lng = isset($s['stop_lon']) ? (float)$s['stop_lon'] : null;
                $name = $s['stop_name'] ?? null;
                if (!$sid || !$lat || !$lng || !$name) continue;

                $routes = $stopRoutes[$sid] ?? [];
                $row = [
                    'stop_id'    => $sid,
                    'stop_name'  => $name,
                    'latitude'   => $lat,
                    'longitude'  => $lng,
                    'routes'     => json_encode(array_keys($routes)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                if ($isMysql) {
                    $row['location'] = DB::raw("ST_GeomFromText('POINT({$lng} {$lat})')");
                }
                $rows[] = $row;
                $total++;
            }
            if ($rows) {
                DB::table('transit_stops')->insert($rows);
            }
        }

        @array_map('unlink', glob($tmpDir . '/*'));
        @rmdir($tmpDir);

        $this->info("Imported {$total} transit stops successfully.");
        return self::SUCCESS;
    }

    private function parseCsv(string $path): array
    {
        if (!file_exists($path)) {
            return [];
        }
        $fh   = fopen($path, 'r');
        $rows = [];
        $hdrs = null;
        while (($line = fgetcsv($fh)) !== false) {
            if ($hdrs === null) {
                $hdrs = array_map('trim', $line);
                continue;
            }
            if (count($line) !== count($hdrs)) {
                continue;
            }
            $rows[] = array_combine($hdrs, $line);
        }
        fclose($fh);
        return $rows;
    }
}
