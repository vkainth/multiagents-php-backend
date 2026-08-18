<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->text('description')->nullable()->after('order');
        });

        $descriptions = [
            [
                'place' => 'Fleetwood Tynehead',
                'city'  => 'Surrey',
                'description' => "Fleetwood is one of Surrey's most family-friendly and rapidly growing neighbourhoods, situated in the northeast part of the city. The area offers a diverse mix of real estate including detached single-family homes, townhouses, and a growing number of condos. Fleetwood is well-served by the 152nd Street and 88th Avenue corridors, with easy access to Highway 1 and the Golden Ears Bridge. Families are drawn to the neighbourhood for its excellent schools, spacious parks, and the Fleetwood Community Centre. The upcoming SkyTrain extension along Fraser Highway is expected to further boost property values and transit connectivity. With a blend of established quiet streets and new master-planned communities, Fleetwood appeals to first-time buyers, upsizing families, and investors looking for strong long-term growth potential in the Metro Vancouver market.",
            ],
            [
                'place' => 'Newton',
                'city'  => 'Surrey',
                'description' => "Newton is a vibrant, multicultural neighbourhood at the heart of Surrey, BC, known for its incredible diversity and strong sense of community. The area features a broad spectrum of housing options, from affordable condos and townhouses to spacious detached homes on large lots. Newton's central location provides residents with quick access to Scott Road, King George Boulevard, and the Newton Exchange transit hub, making commutes throughout Metro Vancouver straightforward. The neighbourhood is home to Newton Athletic Park, Bear Creek Park, and numerous community centres that serve its large and active population. Shopping, dining, and services along 72nd Avenue and King George Boulevard offer everyday convenience. Newton continues to attract first-time buyers and growing families who value affordability, accessibility, and the rich cultural fabric of one of Surrey's most established communities.",
            ],
            [
                'place' => 'Whalley',
                'city'  => 'Surrey',
                'description' => "Whalley, also known as the City Centre of Surrey, is undergoing a dramatic transformation into a modern urban hub in Metro Vancouver. The neighbourhood surrounds the King George SkyTrain station and Surrey Central station, making it one of the most transit-connected communities in the region. High-rise condos and mixed-use developments are reshaping the skyline, offering buyers and investors access to new construction at prices well below comparable Vancouver neighbourhoods. Whalley is home to SFU Surrey, Surrey City Hall, Central City Shopping Centre, and a rapidly expanding healthcare district. The area attracts students, young professionals, and investors seeking high rental demand and long-term appreciation. With billions of dollars in planned infrastructure investment, Whalley represents one of the most compelling real estate opportunities in the Greater Vancouver area.",
            ],
            [
                'place' => 'Cloverdale',
                'city'  => 'Surrey',
                'description' => "Cloverdale is a charming historic town centre neighbourhood in the southeast corner of Surrey, BC, offering a unique blend of small-town character and modern suburban living. Known for its heritage downtown, the Cloverdale Rodeo, and the Cloverdale Fairgrounds, this community has a distinct identity that sets it apart from other Surrey neighbourhoods. Real estate in Cloverdale features a strong selection of detached single-family homes, many with large lots, alongside newer townhouse developments that appeal to families and downsizers. The neighbourhood benefits from easy access to Highway 10 and Highway 15, connecting residents to Langley, White Rock, and the US border. Cloverdale is popular with families who want more space and a quieter lifestyle without sacrificing proximity to Metro Vancouver's job centres and amenities.",
            ],
            [
                'place' => 'Metrotown',
                'city'  => 'Burnaby',
                'description' => "Metrotown is Burnaby's premier urban centre and one of Metro Vancouver's most dynamic real estate markets. Anchored by Metropolis at Metrotown — the largest shopping mall in BC — and served by the Metrotown and Patterson SkyTrain stations on the Expo Line, the neighbourhood offers exceptional transit connectivity to downtown Vancouver and beyond. The Metrotown real estate market is dominated by high-rise condos, ranging from studios and one-bedroom units ideal for young professionals to larger suites suited for families and downsizers. Its walkable, urban environment, abundance of restaurants, services, and entertainment options make it one of the most liveable communities in the Lower Mainland. Investors are drawn to Metrotown for its strong rental demand driven by proximity to BCIT, Simon Fraser University, and major employment hubs.",
            ],
            [
                'place' => 'Yaletown',
                'city'  => 'Vancouver',
                'description' => "Yaletown is one of Vancouver's most sought-after inner-city neighbourhoods, renowned for its converted warehouse lofts, boutique restaurants, and vibrant waterfront lifestyle. Situated along the north shore of False Creek, Yaletown blends heritage industrial architecture with contemporary high-rise condo towers, offering some of the most distinctive real estate in Metro Vancouver. Residents enjoy the Seawall, David Lam Park, and a walkable urban village filled with upscale dining, fitness studios, and designer boutiques along Mainland and Hamilton streets. The neighbourhood is served by the Yaletown–Roundhouse Canada Line station, providing rapid transit access to downtown Vancouver, Richmond, and YVR. Yaletown condos command premium prices reflecting the neighbourhood's lifestyle, location, and prestige, making it a perennial favourite among professionals, downsizers, and international buyers.",
            ],
            [
                'place' => 'Coal Harbour',
                'city'  => 'Vancouver',
                'description' => "Coal Harbour is Vancouver's most prestigious waterfront neighbourhood, occupying a prime position between the downtown financial district and Stanley Park. The neighbourhood is defined by its iconic marina, breathtaking views of the North Shore mountains, Burrard Inlet, and the Vancouver skyline. Real estate in Coal Harbour consists almost exclusively of luxury condominiums and penthouse suites in architecturally significant towers, making it one of the most exclusive markets in Canada. Residents have immediate access to the Coal Harbour Seawall, Harbour Green Park, and the Vancouver Convention Centre, with the Canada Line and West Coast Express nearby. The area attracts high-net-worth buyers, corporate executives, and international investors who value privacy, security, and world-class urban living. Average condo prices in Coal Harbour are among the highest in Metro Vancouver, reflecting its unmatched location and prestige.",
            ],
            [
                'place' => 'Kitsilano',
                'city'  => 'Vancouver',
                'description' => "Kitsilano, affectionately known as \"Kits,\" is one of Vancouver's most beloved westside neighbourhoods, celebrated for its beach culture, heritage homes, and vibrant community spirit. Stretching along the north shore of the Burrard Peninsula, Kitsilano is anchored by the iconic Kitsilano Beach and the outdoor Kits Pool, which draw residents and visitors throughout the summer months. The neighbourhood's real estate landscape encompasses a charming mix of character houses, heritage converted homes, half-duplexes, and low- to mid-rise condominiums along Broadway and 4th Avenue. Kitsilano's tree-lined streets are dotted with independent cafés, yoga studios, organic grocers, and boutique shops that reflect the community's active, health-conscious lifestyle. With excellent access to UBC, downtown Vancouver, and the future Broadway Subway extension, Kitsilano remains one of the most desirable and consistently high-value communities in Metro Vancouver.",
            ],
        ];

        foreach ($descriptions as $data) {
            DB::table('places')
                ->where('type', 'subarea')
                ->whereRaw('LOWER(place) = LOWER(?)', [$data['place']])
                ->whereRaw('LOWER(city) = LOWER(?)', [$data['city']])
                ->update(['description' => $data['description']]);
        }
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
