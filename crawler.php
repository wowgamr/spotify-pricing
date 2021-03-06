<?php

include('lib/selector.inc');

// show errors
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$countrycodes = json_decode(file_get_contents(__DIR__.'/data/countries.json'), true); // get database with countries names
$token = ''; // App ID for Open Exchange Rates
$exchange = json_decode(file_get_contents('https://openexchangerates.org/api/latest.json?app_id='.$token), true); // get exchange rates
$data = '';

function getHtml($url) {
    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($handle, CURLOPT_FRESH_CONNECT, TRUE);
    $response = curl_exec($handle);
    $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
    curl_close($handle);
    return $response;
}

function getPrice($url, $rel) {
    $response = getHtml($url);
    if ($response !== false) {
        $dom = new SelectorDOM($response);

        if (($rel == 'ID') && isset($dom->select('div[data-current-plan-text]')[1]['children'][3]['text'])){ // Indonesia have daily plans, so we take second block
            $price = $dom->select('div[data-current-plan-text]')[1]['children'][3]['text'];
        }
        if (($rel == 'IN' ) && isset($dom->select('div[data-current-plan-text]')[1]['children'][2]['text'])){ // India have daily plans, so we take second block
            $price = $dom->select('div[data-current-plan-text]')[1]['children'][2]['text'];
        }
        elseif ($rel == 'VN' && isset($dom->select('#plans h3')[0]['text'])) { // Vietnam page is unical
            $price = $dom->select('#plans h3')[0]['text'];
        }
        elseif (($rel == 'IS' || $rel == 'DK' || $rel == 'NO' )&& isset($dom->select('div[data-current-plan-text]')[0]['children'][2]['text'])) { // Temporary fix for Iceland, Norway and Denmark
            $price = $dom->select('div[data-current-plan-text]')[0]['children'][2]['text'];
        }
        elseif (isset($dom->select('h2')[0]['text'])) { // Temporary fix for new markets with one tariff plan
            $price = $dom->select('h2')[0]['text'];
        }
        elseif (isset($dom->select('div[data-current-plan-text]')[0]['children'][2]['text'])) { // Standard pages
            $price = $dom->select('div[data-current-plan-text]')[0]['children'][2]['text'];
        };

        $price = str_replace(',', '.', $price);
        $price = preg_replace('/[^,.0-9]/', '', $price);
        $price = ltrim($price, '.');
        $price = rtrim($price, '.');
        if ($rel == 'CL' || $rel == 'CO' || $rel == 'TZ' || $rel == 'UG') {
            $price = preg_replace('/00/', '', $price, 1);
            $price = str_replace('.', '', $price);
        }
        if ($rel == 'KR') {
            $price = str_replace('.', '', $price);
        };
        return $price;
    }
    else {
        echo 'Bad link';
    };
    sleep(1); // timeout because of anti ddos
}

function unique_multidim_array($array, $key) {
    $temp_array = array();
    $i = 0;
    $key_array = array();
   
    foreach($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}

$response = getHtml('https://www.spotify.com/us/select-your-market/');

if ($response !== false)
{
    $dom = new SelectorDOM($response);
    $links = $dom->select('.select-your-market-flex li a'); // get list of countries

    for ($i = 0; $i < count($links); $i++) {
    
        $rel = strtoupper(substr($links[$i]['attributes']['rel'], 0, 2)); // substr 'ca-fr' to 'ca'
        $price = getPrice('https://www.spotify.com/'.$links[$i]['attributes']['rel'].'/premium/', $rel);

        // todo: family plan prices

        $rate = round($exchange['rates'][$countrycodes[$rel]['currency']], 2);
        $convertedPrice = $price/$rate;
        $convertedPrice = round($convertedPrice, 2);
        
        $countries[$i] = ['title' => $countrycodes[$rel]['title'], 'rel' => $rel, 'countryCode' => $countrycodes[$rel]['countryCode'], 'currency' => $countrycodes[$rel]['currency'],
        'region' => $countrycodes[$rel]['region'], 'price' => $price, 'f_price' => '', 'convertedPrice' => $convertedPrice, 'f_convertedPrice' => ''];
    };

    $countries = unique_multidim_array($countries, 'rel');

    foreach($countries as $country) {
        $data = $data.',{"title":"'.$country['title'].'","rel":"'.$country['rel'].'","currency":"'.$country['currency'].'","countryCode":"'.$country['countryCode'].'","region":"'.$country['region'].'","price":'.$country['price'].',"f_price":0,"convertedPrice":'.$country['convertedPrice'].'}';
    };

    $data = substr($data, 1);
    $data = '['.$data.']';

    $file = fopen(__DIR__.'/data/summary.json', 'w');
    fwrite($file, $data);
    fclose($file);
}
else {
	echo 'Bad link';
};

?>