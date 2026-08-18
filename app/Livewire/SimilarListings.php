<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use App\Models\Listings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\UserAgents;
use App\Models\Agents;

class SimilarListings extends Component
{
    public $listing;
    public $similarListingsMode = '';
    public $similarListings = [];

    public $samecity_latest_active = [];
    public $similar_active = [];
    public $similar_sold = [];
    public $samecity_similar_listings = [];

    public function mount($listing) {
        $this->listing = $listing;
        $this->loadSimilarListings();
    }

    public function placeholder()
    {
        return <<<'HTMLCODElvwrPlchrl97dfG7j'
        <div>
                <div id="similar-listings-active" class="col-sm-12 "> <div class="lvwrPlcHldr-loading"><a href="#similar-listings-active">Loading...</a></div></div>
                <div id="similar-listings-sold" class="col-sm-12 "> <div class="lvwrPlcHldr-loading"><a href="#similar-listings-sold">Loading...</a></div></div>
                <div id="similar-listings-samecity_latest_active" class="col-sm-12 "> <div class="lvwrPlcHldr-loading"><a href="#similar-listings-samecity_latest_active">Loading...</a></div></div>
                <div id="similar-listings-samecity" class="col-sm-12 "> <div class="lvwrPlcHldr-loading"><a href="#similar-listings-samecity">Loading...</a></div></div>
        </div>
        HTMLCODElvwrPlchrl97dfG7j;
    }


    /**
     * get_samecity_latest_active_listings [created:14-01-2022]
     * @param  [type] $listing for-listing.city
     * @param  int $limit   max-number of expected-records
     * @return array          [array of listings]
     */
    public function get_samecity_latest_active_listings($listing,$limit=10)
    {
        if(empty($listing)){
            return [];
        }
        $listingsCached = Cache::get( strtolower('sameCityLtstActv__'.$listing->city) );
        if(!empty($listingsCached)){
            // return $listingsCached;
        }
        $listings = Listings::/*with('aphoto')->*/where('table', 'mlsr_listings')->where('status', 'Active')
                    ->where('city', $listing->city)
                    ->where('subarea', $listing->subarea)
                    ->where('listingid', '!=', $listing->listingid)
                    ->orderBy('list_date','desc')->orderBy('inserted','desc')->limit($limit)->get();

        Cache::put( strtolower('sameCityLtstActv__'.$listing->city), $listings, 60*24 );
        return $listings;
    }

    public function get_similar_active_listings($listing)
    {
        $listingprice = $listing->listprice_2;
        $diff = ($listingprice * 15) / 100;
        $min_price = $listingprice - $diff;
        $max_price = $listingprice + $diff;
        
        if($listing->type == "Apartment" || $listing->type =="Townhouse"){
             $min_price = $listingprice - 25000;
             $max_price = $listingprice + 25000;
        }
        
        if($listing->type == "Apartment" || $listing->type =="Townhouse"){
            $listingsCached = Cache::get( str_replace(' ','',strtolower('smlrLsActvs__'.$listing->city.$listing->type.$listing->subarea.$min_price.'_'.$max_price.'_'.$listing->bedrooms."_".$listing->bathstotal) ));
            if(!empty($listingsCached)){
                return $listingsCached
                ->random(min($listingsCached->count(),10))
                ->reject(function($i) use ($listing){return $i->listingid==$listing->listingid;})
                ;
            }
        }
        else{
            $listingsCached = Cache::get( str_replace(' ','',strtolower('smlrLsActvs__'.$listing->city.$listing->type.$listing->subarea.$min_price.'_'.$max_price) ));
            if(!empty($listingsCached)){
                return $listingsCached
                ->random(min($listingsCached->count(),10))
                ->reject(function($i) use ($listing){return $i->listingid==$listing->listingid;})
                ;
            }
        }
        
        

        if($listing->type == "Apartment" || $listing->type =="Townhouse"){
            $listings = Listings::/*with('aphoto')->*/where('table', 'mlsr_listings')->where('status', 'Active')->where('type', $listing->type)->where('city', $listing->city)->where('subarea', $listing->subarea)
            ->where('listprice_2', '>=', $min_price)->where('listprice_2', '<=', $max_price)->where('bedrooms', '=', ($listing->bedrooms))->where('bathstotal', '=', $listing->bathstotal)
            // ->where('listingid', '!=', $listing->listingid) // [disabled:30-06-2022 for caching]
            ->inRandomOrder()->limit(50)->get();

            Cache::put( str_replace(' ','',strtolower('smlrLsActvs__'.$listing->city.$listing->type.$listing->subarea.$min_price.'_'.$max_price.'_'.$listing->bedrooms."_".$listing->bathstotal)), $listings, 60*24 );
        }
        else{
            $listings = Listings::/*with('aphoto')->*/where('table', 'mlsr_listings')->where('status', 'Active')->where('type', $listing->type)->where('city', $listing->city)->where('subarea', $listing->subarea)
            ->where('listprice_2', '>=', $min_price)->where('listprice_2', '<=', $max_price)->where('bedrooms', '>=', ($listing->bedrooms))
            // ->where('listingid', '!=', $listing->listingid) // [disabled:30-06-2022 for caching]
            ->inRandomOrder()->limit(50)->get();

            Cache::put( str_replace(' ','',strtolower('smlrLsActvs__'.$listing->city.$listing->type.$listing->subarea.$min_price.'_'.$max_price)), $listings, 60*24 );
        }
        
        return $listings
        ->random(min(10,$listings->count())) // -($listing->status=='Active'?1:0))) // [random moved up updated:11-07-2022]
        ->reject(function($i) use ($listing){return $i->listingid==$listing->listingid;})
        ;
    }

    public function get_similar_sold_listings($listing)
    {
        $listingsCached = Cache::get( str_replace(' ','',strtolower('smlrLsSlds__'.$listing->city.$listing->type.$listing->subarea) ));
        if(!empty($listingsCached)){
            return $listingsCached
            ->random(min($listingsCached->count(),10))
            ->reject(function($i) use ($listing){return $i->listingid==$listing->listingid;})
            ;
        }

        $listings = Listings::/*with('aphoto')->*/where('table', 'mlsr_listings')->where('status', 'Sold')->where('type', $listing->type)->where('city', $listing->city)->where('subarea', $listing->subarea)
            ->where('sold_date', '>', Carbon::now()->subMonths(6))
            // ->where('listingid', '!=', $listing->listingid) // [disabled:30-06-2022 for caching]
            ->orderBy('sold_date', 'desc')->limit(50)->get();
        
        Cache::put( str_replace(' ','',strtolower('smlrLsSlds__'.$listing->city.$listing->type.$listing->subarea)), $listings, 60*24 );
        return $listings
        ->random(min(10,$listings->count())) // -($listing->status=='Sold'?1:0))) // [random moved up updated:11-07-2022]
        ->reject(function($i) use ($listing){return $i->listingid==$listing->listingid;})
        ;
    }


    public function get_samecity_similar_listings($listing,$limit=10){
        if(empty($listing)){
            return [];
        }

        $listings = array();

        $listings = Listings::/*with('aphoto')->*/where('table', 'mlsr_listings')->where('status', 'Active')
            ->where('city', $listing->city)
            ->where('bedrooms', $listing->bedrooms)
            ->where('bathstotal', $listing->bathstotal)
            ->where('listingid', '!=', $listing->listingid)
            ->where('type', $listing->type)
            ->where('yearbuilt','>=', ($listing->yearbuilt - 10))
            ->where('yearbuilt','<=', ($listing->yearbuilt + 10))
            ->orderBy('list_date','desc')->orderBy('inserted','desc')
            ->orderByRaw("ABS(yearbuilt - YEAR(CURDATE()))")
            ->limit($limit)->get();

        return $listings;

    }



    public function loadSimilarListings()
    {
        $this->samecity_latest_active = $this->get_samecity_latest_active_listings($this->listing);
        $this->similar_active = $this->get_similar_active_listings($this->listing);
        $this->similar_sold = $this->get_similar_sold_listings($this->listing);
        $this->samecity_similar_listings = $this->get_samecity_similar_listings($this->listing);
        // $this->similarListings = Listing::where('status', 'active')->where('city', $this->listing->city)->where('type', $this->listing->type)->limit(10)->get();
    }



    public function render()
    {
        return view('livewire.similar-listings');
    }
}
