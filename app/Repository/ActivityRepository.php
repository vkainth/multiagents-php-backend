<?php

namespace App\Repository;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\UserPropertyViews;
use App\Models\UserSearches;
use App\Models\InsightsActivity;
use App\Models\UserBuildingViews;
use Browser;

class ActivityRepository
{
    public function __construct()
    {
    }

    public function logActivity($type, $status = NULL, $data = NULL, $mls = NULL, $price = NULL, $ref = NULL)
    {
        $user = Auth::user();
        $userEmail = $user->email;
        $email_broken = explode('@', $userEmail);
        if ($email_broken[1] == "pixilink.com") {
            return false;
        }
        $processed_data = NULL;
        if ($data && is_array($data)) {
            $processed_data = json_encode($data);
        } else {
            $processed_data = $data;
        }
        $device = "";
        if (Browser::isMobile()) {
            $device = "Mobile";
        } elseif (Browser::isTablet()) {
            $device = "Tablet";
        } elseif (Browser::isDesktop()) {
            $device = "Desktop";
        }
        $country = NULL;
        if (array_key_exists('HTTP_CF_IPCOUNTRY', $_SERVER)) {
            $country = $_SERVER['HTTP_CF_IPCOUNTRY'];
        }
        $ip = $this->get_client_ip();
        $header = json_encode(request()->header());
        $user_agent = request()->header('user-agent');
        if ($type == 'search') {
            UserSearches::create([
                'userid' => $user->id,
                'uid' => $user->uid,
                'data' => $processed_data,
                'status' => trim($status),
                'mls' => \json_encode($mls)
            ]);
        } elseif ($type == 'property_view') {
            UserPropertyViews::create([
                'userid' => $user->id,
                'uid' => $user->uid,
                'mls' => $mls,
                'status' => trim($status),
                'price' => $price,
                'ref' => $ref,
                'header' => $header,
                'user_agent' => $user_agent,
                'device' => $device,
                'country' => $country,
                'ip' => $ip
            ]);
        } elseif ($type == 'building_view') {
            UserBuildingViews::create([
                'userid' => $user->id,
                'building_id' => $mls,
                'ref' => $ref,
                'user_agent' => $user_agent,
                'device' => $device
            ]);
        }
    }

    public function log_insights_activity($activity, $label, $city, $subarea = null, $period = "15 DAY", $ref = null)
    {
        $user = Auth::user();
        if(!$user){
            return false;
        }
        $userid = $user->id;
        $userEmail = $user->email;
        $email_broken = explode('@', $userEmail);
        if ($email_broken[1] == "pixilink.com") {
            return false;
        }
        $user_agent = request()->header('user-agent');
        $device = "";
        if (Browser::isMobile()) {
            $device = "Mobile";
        } elseif (Browser::isTablet()) {
            $device = "Tablet";
        } elseif (Browser::isDesktop()) {
            $device = "Desktop";
        }
        InsightsActivity::create([
            'userid' => $userid,
            'activity' => $activity,
            'activity_label' => $label,
            'city' => $city,
            'period' => $period,
            'subarea' => $subarea,
            'ref' => $ref,
            'user_agent' => $user_agent,
            'device' => $device
        ]);
    }

    public function get_client_ip()
    {

        $ipaddress = '';
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_X_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if (isset($_SERVER['HTTP_FORWARDED_FOR']))
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if (isset($_SERVER['HTTP_FORWARDED']))
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if (isset($_SERVER['REMOTE_ADDR']))
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
}
