@php
function get_widget_token($lat, $lng, $postalarea, $province = 'BC'){
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.stratareports.ca/location-insights/widgets/get-property-insights?lat='.$lat.'&lng='.$lng.'&province='.$province.'&fsa='.$postalarea,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'x-api-key: BPjvXs9CQ67o2wrQvQgR31kVFgq8F79q9c3u52AA'
        ),
    ));

    $response = curl_exec($curl);
    $error = curl_error($curl);

    curl_close($curl);

    // Check for curl errors
    if ($error) {
        return false;
    }

    // Decode JSON response
    $data = json_decode($response, true);

    // Check if JSON decoding failed
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }

    // Check if response is empty or contains the specific error message
    if (empty($data) || (isset($data['message']) && $data['message'] === "No matching DA found for the provided lat/lng.")) {
        return false;
    }

    // Ensure the token exists before accessing it
    return $data['location_insights']['token'] ?? false;
}

$widget_token = false;

if(isset($main_building)){
    $widget_token = get_widget_token($main_building->latitude, $main_building->longitude, substr($main_building->postalCode, 0, 3));
}
elseif(isset($main_listing)){  // Fixed isset() syntax
    $widget_token = get_widget_token($main_listing->lat, $main_listing->lng, $main_listing->postalarea);
}

@endphp

@if($widget_token)
    <iframe src="https://widgets.stratareports.ca/location-insights/bcch/{{$widget_token}}" loading="lazy" width="100%" height="862" style="border:0;"></iframe>
@endif