@php
/* JSON-LD output via PHP to avoid Blade processing @ symbols */
$_gp = \Illuminate\Support\Facades\Cache::get('google_place_summary', [
    'rating'             => 4.8,
    'user_ratings_total' => 709,
]);
$_jsonld = [
    '@context' => 'https://schema.org',
    '@type'    => 'RealEstateAgent',
    'name'     => 'Hani & Les | BC Condos And Homes',
    'url'      => 'https://www.bccondosandhomes.com',
    'telephone'=> '+16042657975',
    'address'  => [
        '@type'           => 'PostalAddress',
        'streetAddress'   => '300 - 1195 W Broadway',
        'addressLocality' => 'Vancouver',
        'addressRegion'   => 'BC',
        'postalCode'      => 'V6H 3X5',
        'addressCountry'  => 'CA',
    ],
    'aggregateRating' => [
        '@type'       => 'AggregateRating',
        'ratingValue' => (string) ($_gp['rating'] ?? '4.8'),
        'reviewCount' => (string) ($_gp['user_ratings_total'] ?? '709'),
        'bestRating'  => '5',
        'worstRating' => '1',
    ],
    'review' => [
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Sandra M.'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'Les and his team helped us sell our Burnaby condo in just 5 days at asking price. Their network of registered buyers is real — we had multiple showings within 24 hours of listing.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Grace Ngo'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>"I'm a first-time buyer looking for a local expert in the Vancouver market. A heartfelt thank you to Les for taking the initiative to correspond with me. He and his team have been very responsive and helpful."],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Emily Yang'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'An excellent website. You can find the most comprehensive online source for condos and townhouses — building age, construction info, unit counts, and more.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Walter Belasic'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'Hani & Les BC Condos And Homes website is the BEST! Easy to navigate, wealth of info, past sales with pics — your research is made easy enabling real-time decision making.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Michael P.'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'We listed with Hani & Les after two disappointing experiences with other agents. They priced our townhouse correctly from the start and negotiated a fantastic result.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'dina al-kassim'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'This website offers more transparency than others. BCCondosAndHomes shows price histories, price per sqft, scalable maps, and more. As a first-time buyer I benefited from the deeper dive into the facts.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Sean Gill'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'The website is very informative and contains a plethora of information. My interactions were mainly with Les and he is dialed in to assist anyone who comes his way.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'David C.'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'The building strata info on this site saved me from making a bad purchase — I could see the depreciation reports and pet restrictions before even contacting an agent.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'Jennifer K.'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'As out-of-province sellers we needed a team we could trust completely. Les walked us through every step, and the digital marketing campaign they ran for our condo was impressive. Sold above asking.'],
        ['@type'=>'Review','author'=>['@type'=>'Person','name'=>'William Marzoque'],'reviewRating'=>['@type'=>'Rating','ratingValue'=>'5','bestRating'=>'5'],'reviewBody'=>'The easiest tool to search properties in BC I have ever seen — you can add filters, compare historical data and assess precious details pulled from the MLS database.'],
    ],
];
echo '<script type="application/ld+json">'.json_encode($_jsonld, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).'</script>';
@endphp
