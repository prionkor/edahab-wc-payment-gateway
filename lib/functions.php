<?php

// add helper functions here
// call from other files like this WCEPG/function_name()

namespace WCEPG;

function get_var_dump($var)
{
	ob_start();
	var_dump($var);
	return ob_get_clean();
}

function api_creds()
{

	// Option string $plugin_id . _ . $gateway_id . '_settings'
	$options = get_option('woocommerce_edahab_settings', false);
	if (!$options) {
		return false;
	}

	$options = (object) $options;

	$api_key = trim($options->api_key);
	$api_secret = trim($options->api_secret);
	$agent_code = $options->agent_code;
	$sandbox = $options->sandbox == 1;

	if (!$api_key || !$api_secret || !$agent_code) {
		return false;
	}

	return [
		'api_key' => $api_key,
		'api_secret' => $api_secret,
		'agent_code' => $agent_code,
		'sandbox' => $sandbox
	];
}
