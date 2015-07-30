<?php

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '../../../../../../config.php');


[‎29.‎07.‎2015 11:53] Guy Thomas: 
$sites = [
   ['url'] = > 'http://www.slideshare.net/api/oembed/2?url=',
   ['regex'] = > '/(https?:\/\/(www\.)?)(slideshare\.net)\/(.*?)(.*?)(.*?)/is'
];

foreach ($sites as $site){

  if (preg_match($site['regex'],
$text)) {
     echo "A match was found.";
     $newtext = do_oembed($url,
     echo "$newtext";
  }

}
[‎29.‎07.‎2015 11:56] Guy Thomas: 
$sites = [
   [
       ['url1'] = > 'http://www.slideshare.net/api/oembed/2?url=',
       ['url2'] = > 
       ['regex'] = > '/(https?:\/\/(www\.)?)(slideshare\.net)\/(.*?)(.*?)(.*?)/is'
   ]
];
$sites = [
   (object) [
       ['url'] = > 'http://www.slideshare.net/api/oembed/2?url=',
       ['regex'] = > '/(https?:\/\/(www\.)?)(slideshare\.net)\/(.*?)(.*?)(.*?)/is'
   ]
];
$site['regex'] 

v

$site->regex

$a = new stdClass();

is the same as

$a = (object) [];


//require_login($course, false, $cm);
$text = required_param('text', PARAM_RAW);


if (preg_match('/(https?:\/\/(www\.)?)(learningapps\.org)\/(.*?)(.*?)(.*?)/is', $text)) {
    echo "A match was found.";
    $newtext = filter_oembed_learingappscallback($text);
    echo "$newtext";
}

if (preg_match('/(https?:\/\/(www\.)?)(slideshare\.net)\/(.*?)(.*?)(.*?)/is', $text)) {
    echo "A match was found.";
    $newtext = filter_oembed_slidesharecallback($text);
    echo "$newtext";
}

if (preg_match('/(https?:\/\/(www\.)?)(soundcloud\.com)\/(.*?)(.*?)(.*?)/is', $text)) {
    echo "A match was found.";
    $newtext = filter_oembed_soundcloudcallback($text);
    echo "$newtext";
} 

if (preg_match('/(https?:\/\/(www\.)?)(ted\.com)\/talks\/(.*?)(.*?)(.*?)/is', $text)) {
    echo "A match was found.";
    $newtext = filter_oembed_tedcallback($text);
    echo "$newtext";
} 

if (preg_match('/(https?:\/\/(www\.)?)(vimeo\.com)\/(.*?)(.*?)(.*?)/is', $text)) {
    echo "A match was found.";
    $newtext = filter_oembed_vimeocallback($text);
    echo "$newtext";
} 

if (preg_match('/(https?:\/\/(www\.)?)(youtube\.com|youtu\.be|youtube\.googleapis.com)\/(.*?)(.*?)(.*?)/is', $text)) {
    echo "A match was found.";
    $newtext = filter_oembed_youtubecallback($text);
    echo "$newtext";
} 

else {
    echo "A match was not found.";
}



/**
 * Looks for links pointing to learningapps content and processes them.
 *
 * @param $link HTML tag containing a link
 * @return string HTML content after processing.
 */
function filter_oembed_learingappscallback($link) {
    global $CFG;
    $url = "http://learningapps.org/oembed.php?format=json&url=".$link."&format=json&maxwidth=480&maxheight=270";
    $json = filter_oembed_curlcall($url);
    return $json === null ? '<h3>'. get_string('connection_error', 'filter_oembed') .'</h3>' : $json['html'];
}


/**
 * Looks for links pointing to SlideShare content and processes them.
 *
 * @param $link HTML tag containing a link
 * @return string HTML content after processing.
 */
function filter_oembed_slidesharecallback($link) {
    global $CFG;
    $url = "http://www.slideshare.net/api/oembed/2?url=".$link."&format=json&maxwidth=480&maxheight=270";
    $json = filter_oembed_curlcall($url);
    return $json === null ? '<h3>'. get_string('connection_error', 'filter_oembed') .'</h3>' : $json['html'];
}
/**
 * Looks for links pointing to SoundCloud content and processes them.
 *
 * @param $link HTML tag containing a link
 * @return string HTML content after processing.
 */
function filter_oembed_soundcloudcallback($link) {
    global $CFG;
    $url = "https://soundcloud.com/oembed?url=".($link)."&format=json&maxwidth=480&maxheight=270";
    //echo $url;
    $json = filter_oembed_curlcall($url);
    return filter_oembed_vidembed($json);
}

/**
 * Looks for links pointing to TED content and processes them.
 *
 * @param $link HTML tag containing a link
 * @return string HTML content after processing.
 */
function filter_oembed_tedcallback($link) {
    global $CFG;
    $url = "https://www.ted.com/services/v1/oembed.json?url=".$link.'&maxwidth=480&maxheight=270';
    $jsonret = filter_oembed_curlcall($url);
    return filter_oembed_vidembed($jsonret);
}

function filter_oembed_youtubecallback($link) {
    global $CFG;
    $url = "http://www.youtube.com/oembed?url=".$link."&format=json";
    $jsonret = filter_oembed_curlcall($url);
    return filter_oembed_vidembed($jsonret);
}

/**
 * Looks for links pointing to Vimeo content and processes them.
 *
 * @param $link HTML tag containing a link
 * @return string HTML content after processing.
 */
function filter_oembed_vimeocallback($link) {
    global $CFG;
    $url = "http://vimeo.com/api/oembed.json?url=".$link.'&maxwidth=480&maxheight=270';
    $jsonret = filter_oembed_curlcall($url);
    return filter_oembed_vidembed($jsonret);
}



function filter_oembed_curlcall($www) {
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

function filter_oembed_vidembed($json, $params = '') {

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

