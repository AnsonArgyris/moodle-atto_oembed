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

namespace atto_oembed\service;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

class oembed {

    /**
     * @var bool
     */
    protected $success = false;

    /**
     * @var string
     */
    protected $text = '';

    /**
     * @var array
     */
    protected $warnings = [];

    /**
     * @var array|mixed
     */
    protected $providers = [];

    /*
     * @var array
     */
    protected $sites = [];

    /**
     * @var mixed|string
     */
    protected $htmloutput = '';

    /**
     * @var string
     */
    protected $providerjson = '';

    /**
     * @var string
     */
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

        $timeout = 15;
        $providers = [];

        $ret = download_file_content($www, null, null, true, 300, 20, false, NULL, false);

        if ($ret->status == '200') {
            $ret = $ret->results;
        } else {
            $this->warnings[] = 'Failed to load providers from '.$www.' falling back to local version.';
            $ret = file_get_contents(__DIR__.'/../../providers.json');
        }        

        
        $providers = json_decode($ret, true);
        
        if (empty($providers)) {
            throw new \moodle_exception('error:noproviders', 'atto_oembed', '');
        }
              
        return $providers;
    }

    
    /**
     * Check if the provided url matches any supported content providers
     *
     * @return array
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
     *
     * @param array $scehmes
     */
    protected function create_regex_from_scheme(Array $schemes){

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
     *
     * @param string $www
     * @return string
     */
    protected function oembed_curlcall($www) {
        
        $ret = download_file_content($www, null, null, true, 300, 20, false, NULL, false);
        
        $this->providerurl = $www;
        $this->providerjson = $ret->results;
        $result = json_decode($ret->results, true);

        return $result;
    }

    /**
     * Get oembed html.
     *
     * @param string $json
     * @param string $params
     * @return string
     * @throws \coding_exception
     */
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

    /**
     * Get html output
     *
     * @param array $sites
     * @param string $text
     * @return string
     */
    protected function html_output(Array $sites, $text){
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
     * Singleton
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

    /**
     * Get output object
     *
     * @return object
     */
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