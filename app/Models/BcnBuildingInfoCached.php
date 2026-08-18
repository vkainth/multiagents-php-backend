<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
// use Illuminate\Database\Eloquent\Casts\Attribute; //lv-11
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\Buildings;
use App\Helpers\Helper;


class BcnBuildingInfoCached extends Model
{
    use HasFactory;

    protected $connection = 'mysql_mlsr';

    
    protected $table = 'bcn_building_info_cached';

    protected $fillable = ['slug','api_data','sync_source'];

    protected $dates = []; // created_at,updated_at default-included
    
    protected function casts(): array
    {
        return ['api_data' => 'object',];
    }
    


    /* --------- RELATIONS [BEGIN] ---------------- */

    /**
     * Get the building that the comment belongs to.
     */
    public function building(): BelongsTo
    {
        return $this->belongsTo(Buildings::class,'slug','slug')->orderBy('intid')->orderBy('updated','desc');
    }

    /* --------- RELATIONS [END] ---------------- */
    // /**
    //  * return json representation
    //  * @return json|string|null 
    //  */
    // public function getApiDataAttribute()
    // {
    //     return json_encode($this->api_data??'');
    // }

    /**
     * last_synced human-readabe format
     * @return string 
     */
    public function getLastSyncedAttribute()
    {
        return $this->updated_at->diffForHumans();
    }

    /**
     * get generated bcn-api-url
     * @return string|null 
     */
    public function getApiUrlAttribute()
    {
        $building = $this->building()?->first(); //??Buildings::where('slug',$this->slug)->first();

        // $res =  DB::select("SELECT `up` from `bccondosandhomes`.`api_server_status` WHERE `server` = 'bccondos.net'");
        // $server_up = ($res && count($res))?($res[0]->up):'n';
        // if($server_up!='y') return null;
        
        $apiUrl = config('bcch.buildings.bcn_info_api_url','https://www.bccondosandhomes.com/api_building/public/')
            .'?strata='.urlencode(trim($building->strata_no?:'--'))
            .(
                empty(trim(trim($building->strata_no,'-')))
                ? ('&task=trybcnwithid&condoid='.urlencode(trim($building->bcc_id?:'')))
                : ('&streetnum='.urlencode(trim($building->street_no?:''))) 
            )
            .'&city='.urlencode(trim($building->city?:'')).'&bcn_id='.urlencode(trim($building->bcc_id?:''))
            .'&refreshtoken='.date("Ymd"); /* [date("Ymdhis") > every-second fresh-fetch | bloats-cache]*/

        return $apiUrl;

    }

    /**
     * fetchApiData - perform API-call to BCN 
     */
    public function fetchApiData($asJson = false)
    {
        $apiUrl = $this->api_url;

        try {
            $response = Http::connectTimeout(2)->timeout(5)->get($apiUrl);
            if ($response->successful()) {
                return ($asJson?($response->json()):$response->body());
            }
        } catch (\Exception $e) {
            return null;
        }
        return null;

    }

    /**
     * syncNow - perform API-call to BCN and store data into DB
     * @return this|null for chaining: $this return on successfull sync
     */
    public function syncNow()
    {
        if($apiData = $this->fetchApiData(true)){
            $this->updateOrCreate(['slug'=>$this->slug],['api_data'=>$apiData, 'sync_source'=>'syncNow()']);
            $this->refresh();
            return $this;
        }
        return null;
    }

    public function notOlderThan($period = '10 days')
    {
        $threshold = Carbon::now()->sub($period);
        $_cpUpdatedAt = Carbon::parse($this->updated_at);
        $_syncNowRan = false;
        if( $_cpUpdatedAt->lt($threshold) || (!$this->api_data && $_cpUpdatedAt->lt(Carbon::now()->sub('1 days'))) ) {
            $this->syncNow();
            $_syncNowRan = true;
        }
        class_exists('\Debugbar') && \Debugbar::info($this->api_url, 'Bcn-sync-now ran: '.($_syncNowRan?'TRUE':'FALSE'));
        return $this;
    }

    // /**
    //  * boot [2024-08-19] create and save if not exists
    //  * @return this
    //  */
    // public static function boot()
    // {
    //     parent::boot();

    //     static::retrieved(function ($thisModel) {
    //         \Debugbar::info($thisModel);
    //         if (!$thisModel->exists) {
    //             $thisModel->updateOrCreate(['slug'=>$thisModel->slug],['sync_source'=>'boot()']);
    //             $thisModel->syncNow();
    //         }
    //         if($thisModel->wasRecentlyCreated){
    //             $thisModel->syncNow();
    //         }
    //     });
    // }


}
