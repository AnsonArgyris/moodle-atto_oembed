<?php

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '../../../../../../config.php');

//require_login($course, false, $cm);
$text = required_param('text', PARAM_RAW);


//suported sites are entered here
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
    
];

foreach ($sites as $site) {
    	if (preg_match($site['regex'], $text)) {
    		$url = $site['url1'].$text.$site['url2'];
    		$jsonret = oembed_curlcall($url);
    		$newtext = oembed_gethtml($jsonret);    		
    		echo $newtext;
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