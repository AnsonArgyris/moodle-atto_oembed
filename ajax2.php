<?php

define('AJAX_SCRIPT', true);

require_once(dirname(__FILE__) . '../../../../../../config.php');

use atto_oembed\service\oembed;

$instance = oembed::get_instance();

$instance->output_json();