
<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Atto text editor integration version file.
 *
 * @package    atto_oembed
 * @copyright  Erich M. Wappis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '../../../../../../config.php');

$text = required_param('text', PARAM_RAW);

$www ='http://oembed.com/providers.json';

$providers = oembed_curlcall($www);

$sites = oembed_json_rewrite($providers);

$oembed = check_link($sites,$text);

echo $oembed;

function check_link($sites,$text){
    $url2 = '&format=json';
    foreach ($sites as $site) {
        foreach ($site['regex'] as $regex) {
        
            if (preg_match($regex, $text)) {
                $url = $site['endpoint'].'?url='.$text.$url2;
                // return json object
                $jsonret = oembed_curlcall($url);
                
                $newtext = oembed_gethtml($jsonret);            
                return $newtext;
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
    
    foreach ($providers as $provider) {
                $provider_url = $provider["provider_url"];

                $endpoints = $provider['endpoints'];
                $endpoints_array = $endpoints[0];
                $endpoint_url = $endpoints_array['url'];

                $endpoint_url = str_replace('{format}', 'json', $endpoint_url);


            // check if schemes are definded for this provider
            // if not take the provider url for creating a regex

                if (array_key_exists('schemes', $endpoints_array)){
                    $regex_schemes = $endpoints_array['schemes'];
                }
                else {
                    $regex_schemes = array($provider_url);
                }

                $provider_export_array[] = array('provider_name'=>$provider['provider_name'],
                                                 'regex' => create_regex_from_scheme($regex_schemes),
                                                 'endpoint' => $endpoint_url,
                                  );
                
                
            }
    return $provider_export_array;
}

function create_regex_from_scheme($schemes){

    foreach ($schemes as $scheme) {

        //parse url
        //put things in a class

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
