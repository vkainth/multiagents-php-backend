<?php

namespace App\Http\Middleware;

use App\Models\Agents;
use Closure;
use App\Models\Listings;

class setPropertyOpenGraphTags
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */

    public function handle($request, Closure $next)
    {
        $params = $request->route()->parameters();
        if (array_key_exists('slug', $params)) {
            $slug = $params['slug'];
            $listingObj = Listings::where('slug', $slug)->first();
            $listing = false;
            if ($listingObj) {
                $listing = $listingObj->toArray();
                $request->attributes->add(['listingid' => $listing['listingid']]);
            }
        }
        if ($request->get('listingid')) {
            $listingObj = Listings::with('photos')->where('listingid', $request->get('listingid'))->first();
            $listing = false;
            if($listingObj){
                $listing = $listingObj->toArray();
            }
            if ($listing) {
                // $og_tags = '
                //    <meta property="fb:app_id" content="296579054308064" />';
                $og_tags = '
                    <meta property="og:type" content="website" />';
                $og_tags .= '<meta property="og:url" content="https://www.bccondosandhomes.com/listing/' . $listing['slug'] . '" />';

                if (array_key_exists('photos', $listing) && count($listing['photos']) > 0) {
                    $og_tags .= '<meta property="og:image" content="https://media.pixilinkserver.com/' . str_replace('images', '', $listing['photos'][0]['directory'] . $listing['photos'][0]['name']) . '?w=600" />';
                } else {
                    $og_tags .= '<meta property="og:image" content="' . asset('assets/img/no-image.jpg') . '" />';
                }
                if ($listing['status'] == 'Sold') {
                    $og_tags .= '<meta property="og:description" content="' . $listing['streetaddress'] . ', ' . $listing['city'] . ', ' . $listing['province'] . ' - [View Sold Price] ' . $listing['bedrooms'] . ' Bed, ' . $listing['bathstotal'] . 'Bath, ' . $listing['parking'] . ' Parking, ' . $listing['amenity'] . '" />';
                } elseif ($listing['status'] = "Active") {
                    $og_tags .= '<meta property="og:description" content="' . $listing['streetaddress'] . ', ' . $listing['city'] . ', ' . $listing['province'] . ' - [Listed At ' . $listing['listprice'] . '] ' . $listing['bedrooms'] . ' Bed, ' . $listing['bathstotal'] . ' Bath, ' . $listing['parking'] . ' Parking, ' . $listing['amenity'] . '" />';
                }
                $og_tags .= '
                    <meta property="og:image:width" content="1500" />
                    <meta property="og:image:height" content="1000" />
                    <meta property="og:site_name" content="Hani & Les | BC Condos And Homes" />
                    ';
                $og_tags .= '<meta property="twitter:card" content="summary_large_image">';
                $og_tags .= '<meta property="twitter:url" content="https://www.bccondosandhomes.com/listing/' . $listing['slug'] . '" />';

                if ($listing['status'] == 'Sold') {
                    $og_tags .= '<meta property="twitter:description" content="' . $listing['streetaddress'] . ', ' . $listing['city'] . ', ' . $listing['province'] . ' - [View Sold Price] ' . $listing['bedrooms'] . ' Bed, ' . $listing['bathstotal'] . ' Bath, ' . $listing['parking'] . ' Parking, ' . $listing['amenity'] . '" />';
                } elseif ($listing['status'] = "Active") {
                    $og_tags .= '<meta property="twitter:description" content="' . $listing['streetaddress'] . ', ' . $listing['city'] . ', ' . $listing['province'] . ' - [Listed At ' . $listing['listprice'] . '] ' . $listing['bedrooms'] . ' Bed, ' . $listing['bathstotal'] . ' Bath, ' . $listing['parking'] . ' Parking, ' . $listing['amenity'] . '" />';
                }
                if (array_key_exists('photos', $listing) && count($listing['photos']) > 0) {
                    $og_tags .= '<meta property="twitter:image" content="https://media.pixilinkserver.com/' . str_replace('images', '', $listing['photos'][0]['directory'] . $listing['photos'][0]['name']) . '?w=600" />';
                } else {
                    $og_tags .= '<meta property="twitter:image" content="' . asset('assets/img/no-image.jpg') . '" />';
                }

                $request->attributes->add(['og_tags' => $og_tags]);
            }
        }
        return $next($request);
    }
}
