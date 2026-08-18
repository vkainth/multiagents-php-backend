<?php

use Illuminate\Support\Facades\Route;

/* Route::get('/', function () {
    return 'Working...';
}); */

require __DIR__.'/bcchv1/web.php';

/*
|--------------------------------------------------------------------------
| website.pixilink.com bare-region-path proxy (July 2026)
|--------------------------------------------------------------------------
| residencity.ca is retired; its bare-path preview workflow (e.g.
| website.pixilink.com/tricity, /burnaby) now lives natively inside
| pixilink-web's own Next.js middleware (REGION_PREVIEW_HOSTS). However
| this account has no root/WHM access, so Apache cannot be given a
| ProxyPass for these two paths the way southsurreywhiterock.com has.
| This PHP-level proxy is the workaround: it forwards only the known
| region paths (and pixilink-web's static asset prefix) to the Next.js
| container on 127.0.0.1:4000, scoped to the website.pixilink.com domain
| only. Every other path/domain is completely unaffected.
|
| To add a new region here, update the same $regionSlugs list that
| mirrors REGION_PREVIEW_HOSTS' region map in pixilink-web's middleware.ts
| (source of truth is the DB-backed /api-internal/regions endpoint; this
| is just a routing allowlist, not the data itself).
*/
Route::domain('website.pixilink.com')->group(function () {
    $regionSlugs = ['tricity', 'burnaby', 'sharene'];

    $proxyToNextjs = function (\Illuminate\Http\Request $request) {
        $path = '/' . ltrim($request->path(), '/');
        $query = $request->getQueryString();
        $url = 'http://127.0.0.1:4000' . $path . ($query ? '?' . $query : '');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_HTTPHEADER => ['Host: website.pixilink.com'],
            CURLOPT_TIMEOUT => 20,
            CURLOPT_ENCODING => '',
        ]);
        $raw = curl_exec($ch);

        if ($raw === false) {
            curl_close($ch);
            abort(502, 'Region preview backend unreachable');
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $rawHeaders = substr($raw, 0, $headerSize);
        $body = substr($raw, $headerSize);

        $contentType = 'text/html; charset=utf-8';
        $skipHeaders = ['transfer-encoding', 'content-encoding', 'content-length', 'connection', 'keep-alive'];
        $forwardHeaders = [];
        $linkValues = [];
        foreach (preg_split('/\r\n/', $rawHeaders) as $line) {
            if (stripos($line, ':') === false) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $name = trim($name);
            if (in_array(strtolower($name), $skipHeaders, true)) {
                continue;
            }
            if (strtolower($name) === 'content-type') {
                $contentType = trim($value);
            }
            if (strtolower($name) === 'link') {
                $linkValues[] = trim($value);
            } else {
                $forwardHeaders[$name] = trim($value);
            }
        }
        if (!empty($linkValues)) {
            $forwardHeaders['Link'] = implode(', ', $linkValues);
        }

        return response($body, $status ?: 502, $forwardHeaders)->header('Content-Type', $contentType);
    };

    // Bare region paths, e.g. /tricity, /tricity/buildings, /tricity/listing/123
    Route::get('/{region}/{any?}', $proxyToNextjs)
        ->where('region', implode('|', array_map('preg_quote', $regionSlugs)))
        ->where('any', '.*');

    // /agent/{internalSlug}/... — forwarded ONLY for the two region-mapped
    // agents' internal slugs (never all agents), so hardcoded /agent/{slug}
    // links in pixilink-web reach the Next.js middleware, which 308-redirects
    // them back to the canonical /{region}/... path. Every other agent slug
    // falls through untouched to Laravel's own /agent/{slug} dev/staging page
    // below (require __DIR__.'/bcchv1/web.php' at the top of this file).
    $regionAgentSlugs = ['tricity', 'saeed-farhani-ppqu', 'sharene'];
    Route::get('/agent/{slug}/{any?}', $proxyToNextjs)
        ->where('slug', implode('|', array_map('preg_quote', $regionAgentSlugs)))
        ->where('any', '.*');

    // /.well-known/* AI discovery endpoints proxied to pixilink-web
    Route::get('.well-known/{any}', $proxyToNextjs)->where('any', '.*');

    // pixilink-web's Next.js static asset prefix — needed for the region pages
    // above to render styled/interactive; no existing route or public_html
    // file uses this prefix, so it's safe to claim on this domain only.
    Route::get('/_next/{any}', $proxyToNextjs)->where('any', '.*');
});

/* 
 Note: route overrides for testing, keeping at-the-end
 */
if(file_exists(__DIR__.'/dev/tester.php')){ require __DIR__.'/dev/tester.php'; }
