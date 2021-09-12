<?php

namespace WCEPG;

use WP_Error;

class Edahab_Gateway extends \WC_Payment_Gateway
{
	/**
	 * Constructor for the gateway.
	 */
	public function __construct()
	{
		$this->id                 = 'edahab';
		$this->icon               = apply_filters('woocommerce_edahab_icon', '');
		$this->has_fields         = false;
		$this->method_title       = __('Edahab payments', 'wcepg');
		$this->method_description = __('Take payments using Edahab mobile payment gateway.', 'wcepg');
		$this->is_rest_api 				= defined('REST_REQUEST') && REST_REQUEST;
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option('title');
		$this->description  = $this->get_option('description');
		$this->instructions = $this->get_option('instructions');

		// Actions.
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

		// Customer Emails.
		add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields()
	{
		$fields = [
			'enabled' => [
				'title'   => __('Enable/Disable', 'wcepg'),
				'type'    => 'checkbox',
				'label'   => __('Enable Edahab Payment', 'wcepg'),
				'default' => false
			],
			'title' => [
				'title'       => __('Title', 'wcepg'),
				'type'        => 'text',
				'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wcepg'),
				'default'     => __('Edahab Mobile Payment', 'wcepg'),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __('Description', 'wcepg'),
				'type'        => 'textarea',
				'description' => __('Pay using Edahab mobile payment system.', 'wcepg'),
				'default'     => __('Please double check your phone number.', 'wcepg'),
				'desc_tip'    => true,
			],
			'instructions' => [
				'title'       => __('Instructions', 'wcepg'),
				'type'        => 'textarea',
				'description' => __('Instructions that will be added to the thank you page and emails.', 'wc-gateway-offline'),
				'default'     => '',
				'desc_tip'    => true,
			],
			'api_key' => [
				'title' 			=> __('Edahab API Key', 'wcepg'),
				'type'				=> 'text',
				'description' => __('API key found in your Edahab account'),
				'desc_tip' 		=> true,
			],
			'api_secret' => [
				'title' 			=> __('Edahab API Secret', 'wcepg'),
				'type' 				=> 'text',
				'description' => __('API secret found in your Edahab account'),
				'desc_tip' 		=> true,
			],
			'agent_code' 		=> [
				'title' 			=> __('Agent Code', 'wcepg'),
				'type' 				=> 'text',
				'description' => __('Agent code found in your Edahab account'),
				'desc_tip' 		=> true,
			],
			'sandbox' 		=> [
				'title' 			=> __('Test mode', 'wcepg'),
				'type' 				=> 'select',
				'description' => __('Select Yes if you are using test api keys, otherwise choose No'),
				'desc_tip' 		=> true,
				'options' 		=> [
					0 => 'No',
					1 => 'Yes',
				],
				'default' => 0,
			],
		];

		$this->form_fields = apply_filters('wc_edahab_form_fields', $fields);
	}

	public function process_payment($order_id)
	{

		$order = \wc_get_order($order_id);

		// Mark as on-hold (we're awaiting the payment)
		$order->update_status('on-hold', __('Awaiting Edahab payment', 'wcepg'));
		// TODO: More validation needed for this number
		$number = $this->number ? $this->number : sanitize_text_field(trim($_POST[$this->id . '-phone-number']));
        $number = ltrim($number, '0');
        
		if (!ctype_digit($number)) {
			$msg = __('Invalid number! Please only add phone digits, not spaces, signs or hyphens.', 'wcepg');
			if (!$this->is_rest_api) {
				\wc_add_notice($msg, 'error');
				return;
			}

			// for rest api
			return new WP_Error(422, $msg, ['status' => 422]);
		}

		$invoice = $this->create_invoice($order, $number);
        // error_log(json_encode($invoice));
		if ($invoice->status === 'error') {
		    if( !$this->is_rest_api )
			    \wc_add_notice(__('Payment error:', 'wcepg') . $invoice->message, 'error');
			return;
		}

		// Empty cart for non rest cases
		if (!$this->is_rest_api) {
			\WC()->cart->empty_cart();
		}

		// add a order meta with edahab invoice id
		// we will need this on thank you for verifying payments
		$edahabInvoiceId = $invoice->data->InvoiceId;
		$order->add_order_note(__('Edahab invoice id: ', 'wcepg') . $edahabInvoiceId);
		update_post_meta($order_id, '_edahab_invoice', $edahabInvoiceId);

		// Return thankyou redirect
		return array(
			'result'    => 'success',
			'redirect'  => $this->get_transaction_url($order),
			'return_url' => $this->get_return_url($order),
		);
	}

	public function get_transaction_url($order)
	{
		$sandbox = $this->is_sandbox() ? 'sandbox' : 'api';
		$invoice_id = get_post_meta($order->get_id(), '_edahab_invoice', true);
		return "https://edahab.net/$sandbox/payment?invoiceId=" . $invoice_id;
	}

	protected function is_sandbox()
	{
		return $this->get_option('sandbox') == 1;
	}

	/**
	 * Creates invoice using API class
	 */
	protected function create_invoice($order, $number)
	{
		// update order meta with the number
		update_post_meta($order->get_id(), '_customer_edahab_number', $number);
		$creds = $this->api_creds();

		if (!$creds) {
			return false;
		}

		$creds = (object) $creds;
		$price = (float) $order->get_total();

		$api = new API($creds->api_key, $creds->api_secret, $creds->agent_code);
		$api->http_request_timeout = 30;
		$api->sandbox($creds->sandbox);
		return $api->invoice_create($number, $price, $this->get_return_url($order));
	}

	protected function api_creds()
	{
		$api_key = trim($this->get_option('api_key'));
		$api_secret = trim($this->get_option('api_secret'));
		$agent_code = $this->get_option('agent_code');
		$sandbox = $this->get_option('sandbox') == 1;

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

	/**
	 * Output for the order received page.
	 */
	public function thankyou_page()
	{
		if ($this->instructions) {
			echo wpautop(wptexturize($this->instructions));
		}
	}

	/**
	 * Add content to the WC emails.
	 *
	 * @access public
	 * @param WC_Order $order
	 * @param bool $sent_to_admin
	 * @param bool $plain_text
	 */
	public function email_instructions($order, $sent_to_admin, $plain_text = false)
	{
		if ($this->instructions && !$sent_to_admin && 'edahab' === $order->get_payment_method() && $order->has_status('on-hold')) {
			echo wpautop(wptexturize($this->instructions)) . PHP_EOL;
		}
	}

	/**
	 * If There are no payment fields show the description if set.
	 * Override this in your gateway if you have some.
	 */
	public function payment_fields()
	{
		$description = $this->get_description();
		if ($description) {
			echo wpautop(wptexturize($description)); // @codingStandardsIgnoreLine.
		}

		$fields = [];

		$default_fields = [
			'phone-number' => '<p class="form-row form-row-first">
				<label for="' . esc_attr($this->id) . '-phone-number">' . esc_html__('Phone number', 'wcepg') . '&nbsp;<span class="required">*</span></label>
				<input id="' . esc_attr($this->id) . '-phone-number" class="input-text wc-edahab-form-phone-number" type="text" maxlength="10" autocomplete="off" placeholder="" name="' . esc_attr($this->id) . '-phone-number" />
			</p>',
		];

		$fields = wp_parse_args($fields, apply_filters('woocommerce_edahab_form_fields', $default_fields, $this->id));

?>
		<?php do_action('woocommerce_edahab_form_start', $this->id); ?>
		<?php
		foreach ($fields as $field) {
			echo $field; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
		}
		?>
		<?php do_action('woocommerce_edahab_form_end', $this->id); ?>
		<div class="clear"></div>
<?php
	}


	/**
	 * Validate frontend fields.
	 *
	 * Validate payment fields on the frontend.
	 *
	 * @return bool
	 */
	public function validate_fields()
	{
		return true;
	}
}
