<?php

namespace WCEPG;

use function WCEPG\api_creds;

class Plugin
{
	protected static $payment_method_id = 'edahab';

	public static function init()
	{
		REST::init();
		Ajax::init();
		add_filter('woocommerce_payment_gateways', [__CLASS__, 'add_to_gateways']);
		add_action('wp_enqueue_scripts', [__NAMESPACE__ . '\Enqueue', 'init']);
		add_action('woocommerce_checkout_process', [__CLASS__, 'checkout_process']);
		add_action('template_redirect', [__CLASS__, 'verify_payment']);
	}

	public static function add_to_gateways($gateways)
	{
		$gateways[] = __NAMESPACE__ . '\Edahab_Gateway';
		return $gateways;
	}

	public static function checkout_process()
	{
		if ($_POST['payment_method'] != self::$payment_method_id) {
			return;
		}
	}

	public static function verify_payment()
	{
		if (!is_wc_endpoint_url('order-received')) {
			return;
		}

		global $wp;
		$order_id  = absint($wp->query_vars['order-received']);
		if (empty($order_id) || $order_id == 0) {
			return;
		}

		$order = wc_get_order($order_id);
		if ($order->is_paid() || $order->get_payment_method() !== 'edahab') {
			return;
		}

		$invoice_id = get_post_meta($order_id, '_edahab_invoice', true);
		$creds = api_creds();

		if (!$creds) {
			return false;
		}

		$creds = (object) $creds;
		$api = new API($creds->api_key, $creds->api_secret, $creds->agent_code);
		$api->http_request_timeout = 30;
		$api->sandbox($creds->sandbox);

		$invoice = $api->invoice_info($invoice_id);

		if (!$invoice) {
			// failed to verify
			// add wc notice
			return;
		}

		if ($invoice->status === 'error') {
			return;
		}

		if ($invoice->data->status === 'Paid') {
			// Reduce stock levels
			$order->add_order_note(__('Edahab Payment Verified!', 'wcepg'));
			wc_reduce_stock_levels($order_id);
			$order->payment_complete();
		}
	}
}
