<?php

include('lib/selector.inc');

// show errors
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$countrycodes = json_decode(file_get_contents(__DIR__.'/data/countries.json'), true); // get database with countries names
$summary = json_decode(file_get_contents(__DIR__.'/data/summary.json'), true); // load existing prices
$token = ''; // App ID for Open Exchange Rates
$exchange = json_decode(file_get_contents('https://openexchangerates.org/api/latest.json?app_id='.$token), true); // get exchange rates
$data = '';

function getHtml($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/156.0.0.0 Safari/537.36');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $response;
}

function getPrice($url, $rel) {
    $response = getHtml($url);
    if ($response !== false) {
        $dom = new SelectorDOM($response);

        if (($rel == 'ID' || $rel == 'VN' || $rel == 'PH' || $rel == 'TH' || $rel == 'MY' || $rel == 'KE' || $rel == 'ZA' || $rel == 'SA')
        && isset($dom->select('div[data-current-plan-text]')[1]['children'][1]['children'][3]['text'])){ // Some countries have daily plans, so we take second block
            $price = $dom->select('div[data-current-plan-text]')[1]['children'][1]['children'][3]['text'];
        }
        elseif (($rel == 'CA' || $rel == 'AD' || $rel == 'TR')
        && isset($dom->select('div[data-current-plan-text]')[0]['children'][0]['children'][3]['text'])){ // Fix free month label
            $price = $dom->select('div[data-current-plan-text]')[0]['children'][0]['children'][3]['text'];
        }
        elseif (($rel == 'US' && isset($dom->select('div[data-current-plan-text]')[0]['children'][1]['children'][3]['text']))){ // Fix for US promo page
            $price = $dom->select('div[data-current-plan-text]')[0]['children'][1]['children'][3]['text'];
        }
        elseif (isset($dom->select('div[data-current-plan-text]')[0]['children'][0]['children'][2]['text'])) { // Standard pages
            $price = $dom->select('div[data-current-plan-text]')[0]['children'][0]['children'][2]['text'];
        }
        elseif (isset($dom->select('h2')[0]['text'])){ // Temporary fix for new markets with one tariff plan
            $price = $dom->select('h2')[0]['text'];
        };

        // Fix for Cabo Verde
        if ($rel == 'CV') {
            $price = str_replace('$29', '.29', $price);
        }

        // Remove converted price for Bulgaria
        if ($rel == 'BG') {
            $price = preg_replace('/\([^)]*\)/', '', $price);
        }

        $price = str_replace(',', '.', $price);
        $price = preg_replace('/[^,.0-9]/', '', $price);
        $price = ltrim($price, '.');
        $price = rtrim($price, '.');
        // if ($rel == 'CL') {
        //     $price = preg_replace('/00/', '', $price, 1);
        //     $price = str_replace('.', '', $price);
        // };
        if ($rel == 'TZ' || $rel == 'UG' || $rel == 'KR' || $rel == 'ID' || $rel == 'VN' || $rel == 'IQ' || $rel == 'AR' || $rel == 'CO' || $rel == 'NG' || $rel == 'CL' || $rel == 'JP') {
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

$response = getHtml('https://www.spotify.com/us/select-your-country-region/');

if ($response !== false)
{
    $dom = new SelectorDOM($response);
    $links = $dom->select('.encore-light-theme li a'); // get list of countries
    $countries = [];

    for ($i = 0; $i < count($links); $i++) {
        $restoredPrice = 0;
        $rel = strtoupper(substr($links[$i]['attributes']['href'], 1, 2)); // substr 'ca-fr' to 'CA'

        if (strpos($links[$i]['attributes']['href'], '-ar')){
            continue;
        };

        if ($i > 0){
            if ($rel == end($countries)['rel']){
                continue;
            };
        };

        $price = getPrice('https://www.spotify.com'.$links[$i]['attributes']['href'].'premium/', $rel);

        // todo: duo prices
        // todo: family plan prices

        if ($price == NULL){
            $summary_tmp = current(array_filter($summary, function($summary) use ($rel) {
                return $summary['rel'] == $rel;
            }));
            $price = $summary_tmp['price'];
            $restoredPrice = 1;
        };

        $rate = round($exchange['rates'][$countrycodes[$rel]['currency']], 2);
        $convertedPrice = $price/$rate;
        $convertedPrice = round($convertedPrice, 2);

        array_push($countries, ['title' => $countrycodes[$rel]['title'], 'rel' => $rel, 'countryCode' => $countrycodes[$rel]['countryCode'], 'currency' => $countrycodes[$rel]['currency'],
        'region' => $countrycodes[$rel]['region'], 'restoredPrice' => $restoredPrice, 'price' => $price, 'f_price' => '', 'convertedPrice' => $convertedPrice, 'f_convertedPrice' => '']);
    };

    $countries = unique_multidim_array($countries, 'rel');

    foreach($countries as $country) {
        $data = $data.',{"title":"'.$country['title'].'","rel":"'.$country['rel'].'","currency":"'.$country['currency'].'","countryCode":"'.$country['countryCode'].
            '","region":"'.$country['region'].'","restoredPrice":'.$country['restoredPrice'].',"price":'.$country['price'].',"f_price":0,"convertedPrice":'.$country['convertedPrice'].'}';
    };

    $data = substr($data, 1);
    $data = '['.$data.']';

    $check_data = json_decode($data);
    if (json_last_error() === JSON_ERROR_NONE && $data !== '[]') {
        $file = fopen(__DIR__.'/data/summary.json', 'w');
        fwrite($file, $data);
        fclose($file);
    }
    else {
        echo "JSON not valid";
    };
}
else {
	echo 'Bad link';
};

?>