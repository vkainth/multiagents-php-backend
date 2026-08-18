@php
$_la_price_reduced_html = (isset($listing->prev_price) && $listing->prev_price > 0 && $listing->prev_price > $listing->listprice_2)
    ? '<span class="tag" style="white-space:nowrap;font-size:10px;" title="Price reduced from $'.e(number_format($listing->prev_price, 0)).'">Price Reduced &#8595;</span>'
    : '';
@endphp
{!! $_la_price_reduced_html !!}
