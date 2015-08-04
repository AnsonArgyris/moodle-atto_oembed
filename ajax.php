<?php

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '../../../../../../config.php');

//require_login($course, false, $cm);
$text = required_param('text', PARAM_RAW);


/**suported sites are entered here
$sites = [
	'soundcloud' => [
    	'url1'	=> 'https://soundcloud.com/oembed?url=',
    	'url2'	=> '&format=json&maxwidth=480&maxheight=270',
    	'regex'	=> '/(https?:\/\/(www\.)?)(soundcloud\.com)\/(.*?)(.*?)(.*?)/is',
    	],

	'youtube' => [
    	'url1'	=> 'http://www.youtube.com/oembed?url=',
    	'url2'	=> '&format=json',
    	'regex'	=> '/(https?:\/\/(www\.)?)(youtube\.com|youtu\.be|youtube\.googleapis.com)\/(.*?)(.*?)(.*?)/is',
    	],
    
];*/

$www ='http://oembed.com/providers.json';

$providers = oembed_curlcall($www);

$sites = oembed_json_rewrite($providers);

$oembed = check_link($sites,$text);

echo $oembed;
//var_dump($sites);
//echo 'damn';


function check_link($sites,$text){
    $url2 = '&format=json';
    foreach ($sites as $site) {
        foreach ($site['regex'] as $regex) {
        # code...
            //echo $regex;
            if (preg_match($regex, $text)) {
                $url = $site['endpoint'].'?url='.$text.$url2;
                echo $url;
                $jsonret = oembed_curlcall($url);
                echo $jsonret;
                $newtext = oembed_gethtml($jsonret);            
                echo $newtext;
            }
    }
}

}

function oembed_curlcall($www) {
    $crl = curl_init();
    $timeout = 15;
    curl_setopt ($crl, CURLOPT_URL, $www);
    curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt ($crl, CURLOPT_SSL_VERIFYPEER, false);
    $ret = curl_exec($crl);

    // Check if curl call fails.
    if ($ret === false) {
        // Check if error is due to network connection.
        if (in_array(curl_errno($crl), array('6', '7', '28'))) {

            // Try curl call for 3 times pausing 0.5 sec.
            for ($i = 0; $i < 3; $i++) {
                $ret = curl_exec($crl);

                // If we get proper response, break the loop.
                if ($ret !== false) {
                    break;
                }

                usleep(500000);
            }

            // If still curl call failing, return null.
            if ($ret === false) {
                return null;
            }

        } else {
            return null;
        }
    }

    curl_close($crl);
    $result = json_decode($ret, true);
    return $result;
}

function oembed_json_rewrite($providers){
    //$provider = $providers;
    foreach ($providers as $provider) {
                $provider_url = $provider["provider_url"];

                foreach ($provider['endpoints'] as $endpoints) {
                    $endpoint_scheme = $endpoints['schemes'];
                    $endpoint_url = $endpoints['url'];
                    //return $endpoint_url;
                }

                $rexgex[] = array('provider_name'=>$provider['provider_name'],
                                  'regex' => create_regex_from_scheme($endpoints['schemes']),
                                  'endpoint' => $endpoint_url,
                                  );
                
                
            }
    return $rexgex;
}

function create_regex_from_scheme($schemes){

    foreach ($schemes as $scheme) {

        $url1 = preg_split('/(https?:\/\/)/', $scheme);
        $url2 = preg_split('/\//', $url1[1]);
        unset($regex_array);
        foreach ($url2 as $url) {
            $find = ['.','*'];
            $replace =['\.','.*?'];
            $url = str_replace($find, $replace, $url);
            $regex_array[] = '('.$url.')';
        }

        $regex[] = '/(https?:\/\/)'.implode('\/', $regex_array).'/'; 

    }

     return $regex;
}

function oembed_gethtml($json, $params = '') {

    if ($json === null) {
        return '<h3>'. get_string('connection_error', 'filter_oembed') .'</h3>';
    }

    $embed = $json['html'];

    if ($params != ''){
        $embed = str_replace('?feature=oembed', '?feature=oembed'.htmlspecialchars($params), $embed );
    }

    $embedcode = $embed;
    return $embedcode;
}