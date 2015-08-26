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
 * @copyright  Erich Wappis, Guy Thomas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace atto_oembed\service;

defined('MOODLE_INTERNAL') || die();

class oembed {

    protected $success = false;

    protected $text = '';

    protected $warnings = [];

    protected $providers = [];

    protected $sites = [];

    protected $htmloutput = '';

    protected $providerjson = '';

    protected $providerurl =  '';


    /**
     * Constructor - protected singeton.
     */
    protected function __construct() {
        $this->security();
        $this->set_params();
        $this->providers = $this->get_providers();
        $this->sites = $this->get_sites();
        $this->htmloutput = $this->html_output($this->sites, $this->text);
        if (!empty($this->htmloutput)) {
            $this->success = true;
        }
    }


    /**
     * Security checks
     * @throws \moodle_exception
     */
    protected function security() {
        if (!isloggedin()) {
            throw new \moodle_exception('error:notloggedin', 'atto_oembed', '');
        }
    }

    /**
     * Get the media url from the atto dialog window
     */
    protected function set_params() {
        $this->text = required_param('text', PARAM_RAW);
    }

    /**
     * Get the latest providerlist from http://oembed.com/providers.json
     * If connection fails, take local list
     */
    protected function get_providers() {
        $www ='http://oembed.com/providers.json';
        $crl = curl_init();
        $timeout = 15;
        $providers = [];
        curl_setopt ($crl, CURLOPT_URL, $www);
        curl_setopt ($crl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($crl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt ($crl, CURLOPT_SSL_VERIFYPEER, false);

        if (!curl_errno($crl)) {
            $ret = curl_exec($crl);
        } else {
            $ret = false;
        }

        // Check if curl call fails.
        if ($ret === false) {
            // Log warning.
            $this->warnings[] = 'Failed to load providers from '.$www.' falling back to local version. (curl error '.curl_error($crl).')';

            // @todo - use most recently cached version if available.

            // Use local providers file.
            $ret = file_get_contents(__DIR__.'/../../providers.json');
        }

        curl_close($crl);
        $providers = json_decode($ret, true);
        if (empty($providers)) {
            throw new \moodle_exception('error:noproviders', 'atto_oembed', '');
        }
        return $providers;
    }

    
    /**
     * Check if the provided url matches any supported content providers
     */
    protected function get_sites() {

        $sites = [];

        foreach ($this->providers as $provider) {
            $providerurl = $provider["provider_url"];

            $endpoints = $provider['endpoints'];
            $endpointsarr = $endpoints[0];
            $endpointurl = $endpointsarr['url'];

            $endpointurl = str_replace('{format}', 'json', $endpointurl);


            // Check if schemes are definded for this provider
            // If not take the provider url for creating a regex

            if (array_key_exists('schemes', $endpointsarr)){
                $regexschemes = $endpointsarr['schemes'];
            }
            else {
                $regexschemes = array($providerurl);
            }

            $sites[] = [
                'provider_name' => $provider['provider_name'],
                'regex'         => $this->create_regex_from_scheme($regexschemes),
                'endpoint'      => $endpointurl
            ];

        }
        return $sites;
    }

    /**
     * Create regular expressions from the providers list to check
     * for supported providers
     */
    protected function create_regex_from_scheme($schemes){

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

    /**
     * Get the actual json from content provider
     */
    
    protected function oembed_curlcall($www) {
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
        
        $this->providerurl = $www;
        $this->providerjson = $ret;
        $result = json_decode($ret, true);

        return $result;
    }

    protected function oembed_gethtml($json, $params = '') {

        if ($json === null) {
            //return '<h3>'. get_string('connection_error', 'filter_oembed') .'</h3>';
            $this->warnings[] = get_string('connection_error', 'filter_oembed');
            return '';
        }

        $embed = $json['html'];

        if ($params != ''){
            $embed = str_replace('?feature=oembed', '?feature=oembed'.htmlspecialchars($params), $embed );
        }

        $embedcode = $embed;
        return $embedcode;
    }

    protected function html_output($sites, $text){
        $url2 = '&format=json';
        foreach ($sites as $site) {
            foreach ($site['regex'] as $regex) {
                if (preg_match($regex, $text)) {
                    $url = $site['endpoint'].'?url='.$text.$url2;
                    $jsonret = $this->oembed_curlcall($url);
                    return $this->oembed_gethtml($jsonret);
                }
            }
        }
        return '';
    }

    /**
     *
     * @return mixed
     */
    public function get_instance() {
        static $instance;
        if ($instance) {
            return $instance;
        } else {
            return new oembed();
        }
    }

    protected function get_output_obj(){
        $output = (object) [
            'success' => $this->success,
            'warnings' => $this->warnings,
            'htmloutput' => $this->htmloutput,
            'providerjson' => $this->providerjson,
            'providerurl' => $this->providerurl
        ];
        return $output;
    }

    /**
     * Output json
     */
    public function output_json() {
        $output = $this->get_output_obj();
        echo json_encode($output);
        die;
    }



}