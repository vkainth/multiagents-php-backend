<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Listings;
use App\Models\Buildings;
use Carbon\Carbon;

class Helper
{
    public static function get_team_agents($city = '', $area = '', $subarea = '', $postal_area = '', $session_id = '', $flush = false)
    {
        $agents = array();
        if (trim($city) == '' && trim($area) == '' && trim($subarea) == '' && trim($postal_area) != '') {
            $listing = Listings::where('postalarea', $postal_area)->first();
            if ($listing) {
                $city = $listing->city;
                $area = $listing->area;
                $subarea = $listing->subarea;
            }
        }
        if (trim($session_id) != '' && Cache::has('team_agents_' . preg_replace('/\s+/', '_', $city) . '_' . preg_replace('/\s+/', '_', $area) . '_' . preg_replace('/\s+/', '_', $subarea) . '_' . $session_id) && !$flush) {
            return Cache::get('team_agents_' . preg_replace('/\s+/', '_', $city) . '_' . preg_replace('/\s+/', '_', $area) . '_' . preg_replace('/\s+/', '_', $subarea) . '_' . $session_id);
        }
        if (Helper::check_team_agents_available($city, $area, $subarea, $postal_area)) {
            $where = array();
            $sql = "select * from bccondosandhomes.team_members where active  = '1' and profile_image is not null and TRIM(profile_image) != ''";
            if ($city != '') {
                $where[] = "json_contains(cities, '[" . '"' . $city . '"' . "]')";
            }
            if ($area != '') {
                $where[] = "json_contains(areas, '[" . '"' . $area . '"' . "]')";
            }
            if ($subarea != '') {
                $where[] = "json_contains(subarea, '[" . '"' . $subarea . '"' . "]')";
            }
            if (count($where) > 0) {
                $sql .= " AND (" . implode(' OR ', $where) . ")";
            }
            $sql .= " order by rand() limit 3";
        } else {
            $sql = "select * from bccondosandhomes.team_members where active= '1' and id = 6";
        }

        $agents = DB::select($sql);
        Cache::put('team_agents_' . preg_replace('/\s+/', '_', $city) . '_' . preg_replace('/\s+/', '_', $area) . '_' . preg_replace('/\s+/', '_', $subarea) . '_' . $session_id, $agents, Carbon::now()->addHours(2));
        return $agents;
    }

    public static function get_team_phone($city = '', $area = '', $subarea = '', $postal_area = '')
    {
        $city = trim($city);
        $area = trim($area);
        $subarea = trim($subarea);
        if (trim($city) == '' && trim($area) == '' && trim($subarea) == '' && trim($postal_area) != '') {
            $listing = Listings::where('postalarea', $postal_area)->first();
            if ($listing) {
                $city = $listing->city;
                $area = $listing->area;
                $subarea = $listing->subarea;
            }
        }
        if ($city == 'Burnaby' || $city == 'Vancouver' || $subarea == 'Downtown VE' || $subarea == 'Downtown VW' || $subarea == 'Yaletown' || $subarea == 'Coal Harbour' || $subarea == 'False Creek North' || $subarea == 'West End VW' || $area == 'Vancouver West' || $area == 'Vancouver East') {
            return '604-706-1760';
        } elseif ($city == 'Richmond') {
            return '604-670-3953';
        } elseif ($city == 'New Westminister') {
            return '604-670-9605';
        } elseif ($city == 'Tsawwassen' || $city == 'Ladner' || $area == 'Tsawwassen' || $area == 'Ladner') {
            return '604-330-3784';
        } elseif ($city == 'Surrey' || $area == 'N. Delta') {
            return '604-330-3476';
        } elseif ($city == 'White Rock' || $city == 'Langley' || $area == 'Cloverdale') {
            return '604-245-2415';
        } elseif ($city == 'North Vancouver' || $city == 'West Vancouver' || $area == 'North Vancouver' || $area == 'West Vancouver') {
            return '604-243-2696';
        } elseif ($city == 'Coquitlam' || $city == 'Port Moody' || $city == 'Port Coquitlam' || $area == 'Coquitlam' || $area == 'Port Moody') {
            return '604-265-9065';
        } elseif ($city == 'Pitt Meadows' || $city == 'Maple Ridge' || $area == 'Pitt Meadows' || $area == 'Maple Ridge') {
            return '604-245-1041';
        } elseif ($city == 'Abbotsford' || $city == 'Mission' || $city == 'Hope') {
            return '604-265-6833';
        } else {
            return '604-295-4710';
        }
    }

    public static function check_team_agents_available($city = '', $area = '', $subarea = '', $postal_area = '')
    {
        $available = false;
        $city = trim($city);
        $area = trim($area);
        $subarea = trim($subarea);

        if (trim($city) == '' && trim($area) == '' && trim($subarea) == '' && trim($postal_area) != '') {
            $listing = Listings::where('postalarea', $postal_area)->first();
            if ($listing) {
                $city = $listing->city;
                $area = $listing->area;
                $subarea = $listing->subarea;
            }
        }
        if ($city == 'Burnaby' || $city == 'Vancouver' || $subarea == 'Downtown VE' || $subarea == 'Downtown VW' || $subarea == 'Yaletown' || $subarea == 'Coal Harbour' || $subarea == 'False Creek North' || $subarea == 'West End VW' || $area == 'Vancouver West' || $area == 'Vancouver East') {
            $available = true;
        } elseif ($city == 'Richmond') {
            $available = true;
        } elseif ($city == 'New Westminister') {
            $available = true;
        } elseif ($city == 'Tsawwassen' || $city == 'Ladner' || $area == 'Tsawwassen' || $area == 'Ladner') {
            $available = true;
        } elseif ($city == 'Surrey' || $area == 'N. Delta') {
            $available = true;
        } elseif ($city == 'White Rock' || $city == 'Langley' || $area == 'Cloverdale') {
            $available = true;
        } elseif ($city == 'North Vancouver' || $city == 'West Vancouver' || $area == 'North Vancouver' || $area == 'West Vancouver') {
            $available = true;
        } elseif ($city == 'Coquitlam' || $city == 'Port Moody' || $city == 'Port Coquitlam' || $area == 'Coquitlam' || $area == 'Port Moody') {
            $available = true;
        } elseif ($city == 'Pitt Meadows' || $city == 'Maple Ridge' || $area == 'Pitt Meadows' || $area == 'Maple Ridge') {
            $available = true;
        } elseif ($city == 'Abbotsford' || $city == 'Mission' || $city == 'Hope') {
            $available = true;
        }
        return $available;
    }

    public static function get_promotional_team_agent($city = '', $area = '', $subarea = '', $postal_area = '', $session_id = '')
    {
        $agent = null;
        try {
            $agents = Helper::get_team_agents($city, $area, $subarea, $postal_area, $session_id);
            if (!empty($agents)) {
                $agent = $agents[0];
            }
        } catch (\Throwable $e) {
            $agent = null;
        }
        if (!$agent) {
            try {
                $agent = DB::table('bccondosandhomes.team_members')
                    ->where('active', '1')
                    ->whereNotNull('profile_image')
                    ->whereRaw("TRIM(profile_image) != ''")
                    ->orderBy('id')
                    ->first();
            } catch (\Throwable $e) {
                $agent = null;
            }
        }
        return Helper::format_team_agent_for_display($agent, $city, $area, $subarea, $postal_area);
    }

    public static function format_team_agent_for_display($agent = null, $city = '', $area = '', $subarea = '', $postal_area = '')
    {
        $agent = (object) ($agent ?: []);
        $first = trim($agent->first ?? '');
        $last = trim($agent->last ?? '');
        $name = trim($first . ' ' . $last);
        $agency = trim($agent->agency ?? '') ?: 'RE/MAX Crest Realty';
        $email = trim($agent->email ?? '') ?: 'info@bccondosandhomes.com';
        $phone = trim($agent->bccondos_phone ?? '') ?: trim($agent->phone ?? '') ?: Helper::get_team_phone($city, $area, $subarea, $postal_area);
        $digits = preg_replace('/\D+/', '', $phone);
        $tel = $digits ? (strlen($digits) === 10 ? '+1' . $digits : '+' . ltrim($digits, '+')) : '';
        $displayPhone = $phone;
        if (strlen($digits) === 10) {
            $displayPhone = substr($digits, 0, 3) . '-' . substr($digits, 3, 3) . '-' . substr($digits, 6);
        } elseif (strlen($digits) === 11 && substr($digits, 0, 1) === '1') {
            $displayPhone = substr($digits, 1, 3) . '-' . substr($digits, 4, 3) . '-' . substr($digits, 7);
        }
        $image = trim($agent->profile_image ?? '');
        if ($image && !preg_match('/^https?:\/\//i', $image)) {
            $image = ltrim($image, '/');
            if (strpos($image, 'frontend/') === 0) {
                $image = asset($image);
            } else {
                $image = asset('frontend/images/teamagents/' . $image);
            }
        }
        $initialParts = array_filter([$first, $last]);
        $initials = '';
        foreach ($initialParts as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }
        return [
            'first'         => $first ?: 'Our team',
            'last'          => $last,
            'name'          => $name ?: 'BC Condos And Homes Team',
            'initials'      => $initials ?: 'BC',
            'title'         => Helper::properCasePlace($city ?: ($subarea ?: 'BC')) . ' Real Estate Specialist',
            'agency'        => $agency,
            'email'         => $email,
            'phone'         => $displayPhone,
            'tel'           => $tel,
            'sms'           => $tel,
            'profile_image' => $image,
        ];
    }

    /**
     * getStaticTeamAgentsArray [created:19-02-2022]
     * @return [type] [description]
     */
    public static function getStaticTeamAgentsArray(){
        $_teamAgents222 = @include(__DIR__.'/team_agents_array.php');
        return $_teamAgents222??[];
    }

    public static function getTeamAgents(){
        return once(function () {    
            $results = DB::table('bccondosandhomes.team_members')
                ->select('first', 'last', 'email', 'languages','bccondos_phone', 'profile_image', 'video')
                ->where('mls_active', '1')
                ->whereNotNull('mlsid')
                ->where('mlsid', '!=', '')
                ->get();

            //dd($results);
            return $results;
        });
    }
    
     public static function getTeamAgentsNew(){
        return once(function () {    
            $results = DB::table('bccondosandhomes.team_members')
                ->select('first', 'last', 'email', 'languages', 'bccondos_phone', 'phone', 'profile_image', 'video')
                ->where('active', '1')
                ->whereNotNull('profile_image')
                ->where('profile_image', '!=', '')
                ->orderBy('id')
                ->get();

            //dd($results);
            return $results;
        });
    }

    public static function getStaticTopBuilding(){
        $targetCities = [
            'Vancouver', 'Burnaby', 'North Vancouver', 'Richmond',
            'Coquitlam', 'Surrey', 'Langley', 'Abbotsford',
        ];

        // Top 500 most-viewed building IDs in the past year
        $mostViewed = DB::table(DB::raw('`user_building_views`'))
            ->select('building_id', DB::raw('COUNT(*) as view_count'))
            ->where('userid', '!=', 7)
            ->where('created_at', '>=', now()->subYear())
            ->groupBy('building_id')
            ->orderByDesc('view_count')
            ->limit(500)
            ->pluck('view_count', 'building_id');

        $filledCities = [];
        $result       = [];

        // Pass 1: most-viewed buildings sorted by popularity
        // Load building rows only (no photos bulk-load) to stay within memory limits
        $pass1 = Buildings::whereIn('import_id', $mostViewed->keys()->toArray())
            ->whereIn('city', $targetCities)
            ->get()
            ->sortByDesc(fn($b) => $mostViewed->get($b->import_id, 0));

        foreach ($pass1 as $building) {
            $city = $building->city;
            if (isset($filledCities[$city])) {
                continue;
            }
            // Fetch 1 photo via a targeted query (avoids bulk photo load)
            $firstPhoto = $building->photos()
                ->whereNotNull('image_name')->where('image_name', '!=', '')
                ->first();
            if (!$firstPhoto) {
                continue;
            }
            if (!$building->matching_listings()->where('status', 'Active')->exists()) {
                continue;
            }
            $building->setRelation('photos', collect([$firstPhoto]));
            $building->city_label = $city;
            $result[]             = $building;
            $filledCities[$city]  = true;
            if (count($filledCities) >= count($targetCities)) {
                break;
            }
        }

        // Pass 2: cities still missing — cursor through city inventory to avoid loading all at once
        $missingCities = array_filter($targetCities, fn($c) => !isset($filledCities[$c]));

        foreach ($missingCities as $city) {
            Buildings::where('city', $city)
                ->orderByDesc('levels')
                ->cursor()
                ->each(function ($building) use (&$result, &$filledCities, $city) {
                    if (isset($filledCities[$city])) {
                        return false;
                    }
                    $firstPhoto = $building->photos()
                        ->whereNotNull('image_name')->where('image_name', '!=', '')
                        ->first();
                    if (!$firstPhoto) {
                        return;
                    }
                    if (!$building->matching_listings()->where('status', 'Active')->exists()) {
                        return;
                    }
                    $building->setRelation('photos', collect([$firstPhoto]));
                    $building->city_label = $city;
                    $result[]             = $building;
                    $filledCities[$city]  = true;
                    return false;
                });
        }

        return $result;
    }

    /**
     * getStaticCities [created:19-02-2022]
     * @return [type] [description]
     */
    public static function getStaticCities(){
        $cities = ["Victoria","Ladysmith","No City Value","Vancouver","Mayne Island","Saturna Island","Richmond","Surrey","Pender Harbour","Madeira Park","Port Coquitlam","Pender Island","Maple Ridge","Mission","Squamish","Delta","Denman Island","North Vancouver","West Vancouver","White Rock","Sechelt","Burnaby","Coquitlam","Whistler","Tsawwassen","Halfmoon Bay","Central Saanich","Sooke","Sidney","Abbotsford","Langley","Malahat","Chilliwack","Salt Spring Island","Port Moody","Pemberton","Bowen Island","New Westminster","Ganges","Galiano Island","Nanaimo","Hope","Roberts Creek","Gibsons","Lions Bay","Anmore","Belcarra","Crofton","Lake Cowichan","Youbou","Chemainus","Parksville","Qualicum Beach","Courtenay","Comox","Campbell River","Port Alberni","Tofino","Ucluelet","Duncan","Ladner","Tsawwassen","Mission","Maple Ridge","Pitt Meadows","Agassiz","Harrison Hot Springs"];
        return $cities;
    }

    public static function enslugPlace($place){
        return urlencode(strtolower( trim( str_replace(['/','-',' '],['⁄','~','-'],$place??'') ,'-') ));
    }

    public static function deslugPlace($place){
        $ret = trim(ucwords(strtolower(str_replace(['⁄','~','-'],['/','-',' '], urldecode($place??'') ) )));
        $reta = explode(' ', $ret);
        foreach ($reta as $k => $w) {
            if(strlen($w) <= 2 || preg_match('/^\d/', $w) === 1 ){
                $reta[$k] = strtoupper($w);
            }
        }
        return implode(' ', $reta);
    }

    public static function properCasePlace($place){
        return Helper::deslugPlace($place);
    }

    public static function showStaged(){
        if(\Illuminate\Support\Facades\Gate::allows('pixi-devs')){
            return (session()->get('bcch_showStgdChngesv9xLvM')??false);
        }
        return 0;
    }

    public static function encryptURL($url){
        $encKey = "BCC0nD0s@ndH0mes";
        $ciphering = "AES-128-CTR";
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        $encryption_iv = '1234567891021185';
        $encryption = openssl_encrypt($url, $ciphering,
            $encKey, $options, $encryption_iv);
        return $encryption;
    }

    public static function decryptURL($encUrl){
        $encKey = "BCC0nD0s@ndH0mes";
        $ciphering = "AES-128-CTR";
        $decryption_iv = '1234567891021185';
        $iv_length = openssl_cipher_iv_length($ciphering);
        $options = 0;
        $decryption=openssl_decrypt ($encUrl, $ciphering, 
        $encKey, $options, $decryption_iv);
        return $decryption;
    }

    public static function getCityList(){
        $cities = ['Vancouver', 'West Vancouver', 'North Vancouver', 'Burnaby', 'New Westminster', 'Richmond', 'Surrey', 'Delta', 'Langley', 'Abbotsford', 'Chilliwack', 'Coquitlam', 'Tsawwassen', 'Mission', 'Hope', 'Port Coquitlam', 'Whistler', 'Squamish', 'Maple Ridge', 'Mission', 'Pitt Meadows'];
        return $cities;
    }

    /**
     * money_format add for new php-version
     * @param  [type] $format_str Not-used, just kept to keep code same
     * @param  Number $price      Amount to format
     * @return String             Formatted-string
     */
    public static function money_format($format_str = null, $price = null){
        if($price==null || (is_string($price) && empty((float)$price)) ) return '';
        $money_fmt = new \NumberFormatter('en_US', \NumberFormatter::CURRENCY);
        $money_fmt->setAttribute(\NumberFormatter::FRACTION_DIGITS, 0);
        return $money_fmt->formatCurrency($price, 'USD');
    }

    public static function get_featured_listings(){
        $mlsids = DB::table('bccondosandhomes.team_members')->select('mlsid')
        ->where("mls_active",'1')
        ->where("active",'1')
        ->whereNotNull('mlsid')->where('mlsid','!=','')
        ->pluck('mlsid')
        ->toArray()
        ;

        $listings = Listings::with('aphoto')->where('table', 'mlsr_listings')->active()->whereIn('board', array('Real Estate Board of Greater Vancouver', 'Fraser Valley Real Estate Board', 'Chilliwack & District Real Estate Board'))
        ->where(function($query) use ($mlsids){
            $query->whereIn('agent_id', $mlsids)
                    ->orWhereIn('agent2_id', $mlsids)
                    ->orWhereIn('agent3_id', $mlsids);
        })->orderBy('list_date','desc')->get();

        return $listings;
    }

    /**
     * listing_agentInfo used-in:Listings-model, to avoid repeating-queries|memoizing-here [2025-06-26]
     * @param  string       $city       listing>city
     * @param  null         $agentId    listing>agent-id
     * @param  bool|boolean $isFeatured listing>featured?
     * @return object|false                   agent|false
     */
    public static function listing_agentInfo(string $city = '', string|int|null $agentId = null, bool $isFeatured = false): object|false
    {
        $isSpecialCity = in_array(strtolower($city), ['surrey', 'langley']) && ! $isFeatured;
        $cacheKey = $isSpecialCity ? "{$city}|0" : "other|{$agentId}";

        return once(function () use ($city, $agentId, $isFeatured) {
            $db = DB::connection('mysql_boards');
            if ($agentId) {
                return $db->selectOne("SELECT * FROM bccondosandhomes.team_members WHERE mlsid = ?", [$agentId]);
            }

            return false;
        }, $cacheKey);
    }

    /**
     * isFeaturedAgent used-in:Listings-model, to avoid repeating-queries|meoizing-here [2025-06-26]
     * @param  int|null    $agentId listing>agent-id
     * @return boolean     true|false
     */
    public static function isFeaturedAgent(string|int|null $agentId): bool
    {
        if (! $agentId) {
            return false;
        }

        $activeMlsIds = once(function () {
            return collect(DB::connection('mysql_boards')->select("SELECT mlsid FROM bccondosandhomes.team_members WHERE mls_active = 1"))
            ->pluck('mlsid')
            ->map(fn($id) => strtolower(trim($id)))
            ->toArray();
        });

        return in_array(strtolower(trim($agentId)), $activeMlsIds, true);
    }
    
    public static function createJWTToken(array $payload, string $secret = null, string $algo = 'HS256')
    {
        if (!$secret) {
            $secret = env('JWT_SECRET', 'your_default_secret');
        }

        $header = [
            'typ' => 'JWT',
            'alg' => $algo,
        ];

        $base64UrlHeader = rtrim(strtr(base64_encode(json_encode($header)), '+/', '-_'), '=');
        $base64UrlPayload = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }
    
    public static function verifyJWTToken(string $jwt, string $secret = null)
    {
        if (!$secret) {
            $secret = env('JWT_SECRET', 'your_default_secret');
        }

        $tokenParts = explode('.', $jwt);
        if (count($tokenParts) !== 3) {
            return false;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $tokenParts;

        $signature = base64_decode(strtr($signatureEncoded, '-_', '+/'));
        $expectedSignature = hash_hmac('sha256', $headerEncoded . '.' . $payloadEncoded, $secret, true);

        if (!hash_equals($signature, $expectedSignature)) {
            return false;
        }

        $payload = json_decode(base64_decode(strtr($payloadEncoded, '-_', '+/')), true);

        if (isset($payload['exp']) && time() >= $payload['exp']) {
            return false;
        }

        return $payload;
    }

}
