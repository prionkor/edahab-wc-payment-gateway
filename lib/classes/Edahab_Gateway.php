<?php 

namespace WCEPG;

class Edahab_Gateway extends \WC_Payment_Gateway {
	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
		$this->id                 = 'edahab';
		$this->icon               = apply_filters( 'woocommerce_edahab_icon', '' );
		$this->has_fields         = false;
		$this->method_title       = __( 'Edahab payments', 'wcepg' );
		$this->method_description = __( 'Take payments using Edahab mobile payment gateway.', 'wcepg' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title        = $this->get_option( 'title' );
		$this->description  = $this->get_option( 'description' );
		$this->instructions = $this->get_option( 'instructions' );

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_cheque', array( $this, 'thankyou_page' ) );

		// Customer Emails.
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$fields = [
			'enabled' => [
				'title'   => __( 'Enable/Disable', 'wcepg' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable Edahab Payment', 'wcepg' ),
				'default' => false
			],
			'title' => [
				'title'       => __( 'Title', 'wcepg' ),
				'type'        => 'text',
				'description' => __( 'This controls the title for the payment method the customer sees during checkout.', 'wcepg' ),
				'default'     => __( 'Edahab Mobile Payment', 'wcepg' ),
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => __( 'Description', 'wcepg' ),
				'type'        => 'textarea',
				'description' => __( 'Pay using Edahab mobile payment system.', 'wcepg' ),
				'default'     => __( 'Please double check your phone number.', 'wcepg' ),
				'desc_tip'    => true,
			],
			'instructions' => [
					'title'       => __( 'Instructions', 'wcepg' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page and emails.', 'wc-gateway-offline' ),
					'default'     => '',
					'desc_tip'    => true,
			],
			'api_key' => [
				'title' 			=> __( 'Edahab API Key', 'wcepg' ),
				'type'				=> 'text',
				'description' => __( 'API key found in your Edahab account'),
				'desc_tip' 		=> true,
			],
			'api_secret' => [
				'title' 			=> __( 'Edahab API Secret', 'wcepg' ),
				'type' 				=> 'text',
				'description' => __( 'API secret found in your Edahab account'),
				'desc_tip' 		=> true,
			],
			'agent_code' 		=> [
				'title' 			=> __( 'Agent Code', 'wcepg' ),
				'type' 				=> 'text',
				'description' => __( 'Agent code found in your Edahab account'),
				'desc_tip' 		=> true,
			],
		];

		$this->form_fields = apply_filters( 'wc_edahab_form_fields', $fields );
	}

	public function process_payment( $order_id ) {
		
		$order = wc_get_order( $order_id );
						
		// Mark as on-hold (we're awaiting the payment)
		$order->update_status( 'on-hold', __( 'Awaiting Edahab payment', 'wcepg' ) );
						
		// Reduce stock levels
		// $order->reduce_order_stock();
						
		// Remove cart
		WC()->cart->empty_cart();
						
		// Return thankyou redirect
		return array(
				'result'    => 'success',
				'redirect'  => $this->get_return_url( $order )
		);
	}

		/**
	 * Output for the order received page.
	 */
	public function thankyou_page() {
		if ( $this->instructions ) {
				echo wpautop( wptexturize( $this->instructions ) );
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
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( $this->instructions && ! $sent_to_admin && 'edahab' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
			echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
		}
	}

		/**
	 * If There are no payment fields show the description if set.
	 * Override this in your gateway if you have some.
	 */
	public function payment_fields() {
		$description = $this->get_description();
		if ( $description ) {
			echo wpautop( wptexturize( $description ) ); // @codingStandardsIgnoreLine.
		}

		$fields = array();

		$default_fields = array(
			'phone-number' => '<p class="form-row form-row-first">
				<label for="' . esc_attr( $this->id ) . '-phone-number">' . esc_html__( 'Phone number', 'wcepg' ) . '&nbsp;<span class="required">*</span></label>
				<input id="' . esc_attr( $this->id ) . '-phone-number" class="input-text wc-edahab-form-phone-number" type="text" maxlength="9" autocomplete="off" placeholder="" name="' . esc_attr( $this->id ) . '-phone-number" />
			</p>',
		);

		$fields = wp_parse_args( $fields, apply_filters( 'woocommerce_edahab_form_fields', $default_fields, $this->id ) );
		
		?>
			<?php do_action( 'woocommerce_edahab_form_start', $this->id ); ?>
			<?php
			foreach ( $fields as $field ) {
				echo $field; // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped
			}
			?>
			<?php do_action( 'woocommerce_edahab_form_end', $this->id ); ?>
			<div class="clear"></div>
		<?php 
	}
}