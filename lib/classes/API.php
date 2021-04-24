<?php

namespace WCEPG;
// use function WCEPG\get_var_dump;

class API
{

	/**
	 * @var boolean $sandbox
	 */
	protected $sandbox = false;

	/**
	 * @var string $api_key
	 * @var string $api_secret
	 * @var string $agent_code
	 */

	/**
	 * @var int $http_request_timeout
	 */
	public $http_request_timeout = 5;

	public function __construct($api_key, $api_secret, $agent_code)
	{
		$this->api_key = $api_key;
		$this->api_secret = $api_secret;
		$this->agent_code = $agent_code;
	}


	public function invoice_create($number, $amount, $return_url)
	{

		/**
		 * Set a precision because sometimes php creates a large numbers of decimal
		 * values for floating point numbers
		 * 56.99 can become 56.99000000000000000000023
		 * Which edahab will reject as invalid data
		 * 
		 */
		ini_set("precision", 14);
		ini_set("serialize_precision", -1);

		$request_param = [
			"ApiKey" => $this->api_key,
			"EdahabNumber" => $number,
			"Amount" => $amount,
			"AgentCode" => $this->agent_code,
			"ReturnUrl" => $return_url,
		];

		$json = json_encode($request_param, JSON_UNESCAPED_SLASHES);
		$hash = $this->hash($json, $this->api_secret);
		$url = $this->api_base_url() . "/IssueInvoice?hash=" . $hash;

		$res = wp_remote_post($url, [
			'headers'     => ['Content-Type' => 'application/json'],
			'body'        => $json,
			'data_format' => 'body',
			'timeout'			=> $this->http_request_timeout,
		]);

		$code = wp_remote_retrieve_response_code($res);
		$resp = [
			'status' => 'error',
		];

		if (200 !== $code) {
			// return error
			$resp['message'] = 'Response code is not 200';
			// error_log( get_var_dump( $res ) );
			return (object) $resp;
		}

		return (object) ['status' => 'success', 'data' => json_decode(wp_remote_retrieve_body($res))];
	}

	public function invoice_info($invoice_id)
	{
		$data = [
			'ApiKey' => $this->api_key,
			'InvoiceId' => (int) $invoice_id,
		];

		$json = json_encode($data);
		$hash = $this->hash($json, $this->api_secret);
		$url = $this->api_base_url() . '/CheckInvoiceStatus?hash=' . $hash;

		$res = wp_remote_post($url, [
			'headers'     => ['Content-Type' => 'application/json'],
			'body'        => $json,
			'data_format' => 'body',
			'timeout'			=> $this->http_request_timeout,
		]);

		$code = wp_remote_retrieve_response_code($res);
		$resp = [
			'status' => 'error',
		];

		if (200 !== $code) {
			// return error
			$resp['message'] = 'Response code is not 200';
			// error_log( get_var_dump( $res ) );
			return (object) $resp;
		}

		return (object) ['status' => 'success', 'data' => json_decode(wp_remote_retrieve_body($res))];
	}

	public function sandbox($flag)
	{
		$this->sandbox = $flag;
	}

	protected function api_base_url()
	{
		$sandbox = $this->sandbox ? 'sandbox' : 'api';
		return "https://edahab.net/$sandbox/api";
	}

	protected function hash($json, $secret)
	{
		return hash('SHA256', $json . $secret);
	}
}
