@php
$user = Illuminate\Support\Facades\Auth::user();
$currentRoute = Illuminate\Support\Facades\Route::currentRouteName();
$now = date('Y-m-d H:i:s');
@endphp
@if($user && $user->id != 7 && $user->phone_verified && $currentRoute == "building-detail-page" && $building)
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const formData = new FormData();
      formData.append('action', 'buildingview');
      formData.append('buildingid', "{{ $building->id }}");
      formData.append('_token', "{{ csrf_token() }}");

      fetch("{{ ('/send-info-to-followupboss') }}", {
        method: "POST",
        headers: {
          'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.text();
      })
      .then(data => {
        console.log("Successfully sent building view to FollowUpBoss");
      })
      .catch(error => {
        console.error("Error sending building view to FollowUpBoss:", error);
      });
    });
  </script>
@endif
@if($user && $user->id != 7 && $user->phone_verified && $currentRoute == "listing-detail-page2" && $listing)
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const formData = new FormData();
      formData.append('action', 'listingview');
      formData.append('listingid', "{{ $listing->listingid }}");
      formData.append('_token', "{{ csrf_token() }}");

      fetch("{{ ('/send-info-to-followupboss') }}", {
        method: "POST",
        headers: {
          'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.text();
      })
      .then(data => {
        console.log("Successfully sent listing view to FollowUpBoss");
      })
      .catch(error => {
        console.error("Error sending listing view to FollowUpBoss:", error);
      });
    });
  </script>
@endif
@if(1==0 && $user && $user->id != 7)
    @if($currentRoute == "building-detail-page" && $building)
     @php
        $curl = curl_init();
        curl_setopt_array($curl, [
          CURLOPT_URL => "https://api.followupboss.com/v1/events",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode([
            'person' => [
                'contacted' => false,
                'firstName' => $user->first,
                'lastName' => $user->last,
                'stage' => 'Lead',
                'source' => 'website',
                'sourceUrl' => route('building-detail-page', $building->slug),
                'emails' => [
                        [
                                        'value' => $user->email
                        ]
                ],
                'phones' => [
                        [
                                        'value' => ($user->phone?$user->phone_country_code: '') . ($user->phone ?? '')
                        ]
                ],
                'id' => $user->followupboss_people_id??''
            ],
            'property' => [
                'street' => $building->street_no.' '.ucfirst(strtolower($building->street_name)).' '.ucfirst(strtolower($building->street_type)),
                'city' => ucfirst(strtolower($building->city)),
                'state' => 'BC',
                'code' => $building->postalcode,
                'url' => route('building-detail-page', $building->slug),
            ],
            'source' => 'bccondosandhomes.com',
            'system' => 'website_api',
            'type' => 'Viewed Property'
          ]),
          CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "authorization: Basic " . config('services.followupboss.api_key'),
            "content-type: application/json"
          ],
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        $responseData = json_decode($response, true);

        if (!$user->followupboss_people_id && isset($responseData['id'])) {
            $user->followupboss_people_id = $responseData['id'];
            $user->save();
        }

    @endphp
 
    @elseif($currentRoute == "listing-detail-page2" && $listing)
    
    
    @php
        $curl = curl_init();
        curl_setopt_array($curl, [
          CURLOPT_URL => "https://api.followupboss.com/v1/events",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => json_encode([
            'person' => [
                'contacted' => false,
                'firstName' => $user->first,
                'lastName' => $user->last,
                'stage' => 'Lead',
                'source' => 'website',
                'sourceUrl' => route('listing-detail-page2',['slug'=>$listing->slug]),
                'price' => $listing->listprice_2,
                'emails' => [
                        [
                                        'value' => $user->email
                        ]
                ],
                'phones' => [
                        [
                                        'value' => ($user->phone?$user->phone_country_code: '') . ($user->phone ?? '')
                        ]
                ],
                'id' => $user->followupboss_people_id??''
            ],
            'property' => [
                'street' => ucwords(strtolower($listing->streetaddress)),
                'city' => $listing->city,
                'state' => $listing->province,
                'code' => $listing->postalcode,
                'mlsNumber' => $listing->listingid,
                'price' => $listing->listprice_2,
                'url' => route('listing-detail-page2',['slug'=>$listing->slug]),
                'bedrooms' => $listing->bedrooms,
                'bathrooms' => $listing->bathstotal,
                'area' => $listing->livingarea_2,
                'lot' => $listing->lotsize
            ],
            'source' => 'bccondosandhomes.com',
            'system' => 'website_api',
            'type' => 'Viewed Property'
          ]),
          CURLOPT_HTTPHEADER => [
            "accept: application/json",
            "authorization: Basic " . config('services.followupboss.api_key'),
            "content-type: application/json"
          ],
        ]);
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        $responseData = json_decode($response, true);

        if (!$user->followupboss_people_id && isset($responseData['id'])) {
            $user->followupboss_people_id = $responseData['id'];
            $user->save();
        }

    @endphp

    @endif
@endif