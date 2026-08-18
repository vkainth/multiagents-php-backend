<p>Hi there,</p>
<p>Following are the property details,</p>
<table>
    <tr>
        <th align="left">MLS</th>
        <td>{{$property['mls']}}</td>
    </tr>
    <tr>
        <th align="left">Status</th>
        <td>{{$property['status']}}</td>
    </tr>
    <tr>
        <th align="left">Type</th>
        <td>{{$property['type']}}</td>
    </tr>
    <tr>
        <th align="left">Address</th>
        <td>{{$property['streetaddress']}}</td>
    </tr>
    <tr>
        <th align="left">City</th>
        <td>{{$property['city']}}</td>
    </tr>
    <tr>
        <th align="left">Postal Code</th>
        <td>{{$property['postalcode']}}</td>
    </tr>
    <tr>
        <th align="left">Year Built</th>
        <td>{{$property['yearbuilt']}}</td>
    </tr>
    <tr>
        <th align="left">Virtual Tour</th>
        <td>{{$property['virtualtour']}}</td>
    </tr>
    <tr>
        <th align="left">Beds</th>
        <td>{{$property['bedrooms']}}</td>
    </tr>
    <tr>
        <th align="left">Kitchens</th>
        <td>{{$property['kitchens']}}</td>
    </tr>
    <tr>
        <th align="left">Baths</th>
        <td>{{$property['baths']}}</td>
    </tr>
    <tr>
        <th align="left">Agent</th>
        <td>{{$property['agent_name']}}</td>
    </tr>
    <tr>
        <th align="left">Listed</th>
        <td>{{Carbon\Carbon::parse($property['list_date'])->format('Y/m/d')}}</td>
    </tr>
    <tr>
        <th align="left">List Price</th>
        <td>{{$property['listprice']}}</td>
    </tr>
    <tr>
        <th align="left">Sold</th>
        <td>{{Carbon\Carbon::parse($property['sold_date'])->format('Y/m/d')}}</td>
    </tr>
    <tr>
        <th align="left">Sold Price</th>
        <td>{{$property['soldprice']}} ({{$property['soldprice_of_listingprice']}}% of list price)</td>
    </tr>
    <tr>
        <th align="left">Days on Market</th>
        <td>{{$property['daysOnMarket']}}</td>
    </tr>
</table>
<p>Last Orders</p>
<table border="1">
    <tr>
        <th>Item</th>
        <th>Description</th>
        <th>Supplier</th>
        <th>Created</th>
    </tr>
    @foreach($property['orders'] as $order)
    <tr>
        <td>{{$order['item']}}</td>
        <td>{{$order['description']}}</td>
        <td>{{$order['supplier']}}</td>
        <td>{{$order['inserted']}}</td>
    </tr>
    @endforeach
</table>