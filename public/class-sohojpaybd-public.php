<?php

class WC_Sohojpaybd_Gateway extends WC_Payment_Gateway
{
	/**
	 * Constructor for the gateway.
	 */
	public function __construct()
	{
		$this->id                 = 'sohojpaybd';
		$this->icon               = plugin_dir_url(__FILE__) . 'images/logo.png'; // Adjust path as needed
		$this->has_fields         = false;
		$this->method_title       = __('Sohojpaybd', 'sohojpaybd');
		$this->method_description = __('Pay with Sohojpaybd', 'sohojpaybd');

		// Supports
		$this->supports = array(
			'products'
		);

		// Initialize form fields
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables
		$this->title       = $this->get_option('title');
		$this->description = $this->get_option('description');
		$this->enabled     = $this->get_option('enabled');

		// Actions
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
		add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'handle_webhook'));
	}

	/**
	 * Initialize Gateway Settings Form Fields.
	 */
	public function init_form_fields()
	{
		$this->form_fields = array(
			'enabled' => array(
				'title'       => __('Enable/Disable', 'sohojpaybd'),
				'label'       => __('Enable Sohojpaybd', 'sohojpaybd'),
				'type'        => 'checkbox',
				'description' => __('Enable Sohojpaybd Plugin', 'sohojpaybd'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'is_digital' => array(
				'title'       => __('Enable/Disable Digital product', 'sohojpaybd'),
				'label'       => __('Enable Digital product', 'sohojpaybd'),
				'type'        => 'checkbox',
				'description' => __('Check to sell digital product', 'sohojpaybd'),
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'title' => array(
				'title'       => __('Title', 'sohojpaybd'),
				'type'        => 'text',
				'description' => __('This controls the title which the user sees during checkout.', 'sohojpaybd'),
				'default'     => __('Sohojpaybd', 'sohojpaybd'),
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => __('Description', 'sohojpaybd'),
				'type'        => 'textarea',
				'description' => __('This controls the description which the user sees during checkout.', 'sohojpaybd'),
				'default'     => __('Pay with Sohojpaybd', 'sohojpaybd'),
				'desc_tip'    => true,
			),
			'currency_rate' => array(
				'title'       => __('Enter USD Rate', 'sohojpaybd'),
				'type'        => 'number',
				'description' => '',
				'default'     => '85',
				'desc_tip'    => true,
			),
			'payment_site' => array(
				'title'             => '',
				'type'              => 'hidden',
				'description'       => '',
				'default'           => 'https://secure.sohojpaybd.com/',
				'desc_tip'          => false,
			),
		);
	}

	/**
	 * Process Payment.
	 *
	 * @param int $order_id The order ID.
	 * @return array
	 */
	public function process_payment($order_id)
	{
		$order = wc_get_order($order_id);
		$current_user = wp_get_current_user();

		$subtotal = WC()->cart->subtotal;
		$shipping_total = WC()->cart->get_shipping_total();
		$fees = WC()->cart->get_fee_total();
		$discount_excl_tax_total = WC()->cart->get_cart_discount_total();
		$discount_tax_total = WC()->cart->get_cart_discount_tax_total();
		$discount_total = $discount_excl_tax_total + $discount_tax_total;
		$total = $subtotal + $shipping_total + $fees - $discount_total;

		if ($order->get_currency() == 'USD') {
			$total = $total * $this->get_option('currency_rate');
		}

		if ($order->get_status() != 'completed') {
			$order->update_status('pending', __('Customer is being redirected to Sohojpaybd', 'sohojpaybd'));
		}
		$usr_number = get_user_meta($current_user->ID, 'phone_number', true);


		$data = array(
			"cus_name"    => $current_user->user_firstname,
			"cus_email"   => $current_user->user_email,
			"cus_phone"   => $usr_number,
			"amount"      => $total,
			"webhook_url" => site_url('/?wc-api=wc_sohojpaybd_gateway&order_id=' . $order->get_id()),
			"success_url" => $this->get_return_url($order),
			"cancel_url"  => wc_get_checkout_url()
		);

		$header = array(
			"api" => get_option('sohojpaybd_api_key'),
			"url" => $this->get_option('payment_site') . "api/payment/create"
		);

		$response = $this->create_payment($data, $header);
		$data = json_decode($response, true);

		return array(
			'result'   => 'success',
			'redirect' => $data['payment_url']
		);
	}

	/**
	 * Create Payment Request.
	 *
	 * @param array $data   Payment data.
	 * @param array $header Header data.
	 * @return mixed
	 */
	public function create_payment($data = "", $header = '')
	{
		$headers = array(
			'Content-Type: application/json',
			'SOHOJPAY-API-KEY: ' . $header['api'],
		);
		$url = $header['url'];
		$curl = curl_init();
		$data = json_encode($data);

		curl_setopt_array($curl, array(
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_POSTFIELDS     => $data,
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_VERBOSE        => true
		));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

		$response = curl_exec($curl);
		curl_close($curl);
		return $response;
	}

	/**
	 * Update Order Status.
	 *
	 * @param object $order The order object.
	 * @return bool
	 */

	/**
	 * Insert or Update Transaction.
	 *
	 * @param array $data Transaction data.
	 * @return bool|WP_Error
	 */
	public function sohojpay_insert_transaction($data)
	{
		global $wpdb;
		$table_name = $wpdb->prefix . 'sohojpay_transactions';

		// Check if the transaction already exists
		$existing_transaction = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table_name WHERE trx_id = %s",
			sanitize_text_field($data['trx_id'])
		));

		$data = apply_filters('sohojpaybd_before_insert_transaction', $data);

		if ($existing_transaction) {
			// Update the existing transaction
			$update = $wpdb->update(
				$table_name,
				[
					'order_id'  => sanitize_text_field($data['order_id']),
					'payment_id' => sanitize_text_field($data['payment_id']),
					'status'    => sanitize_text_field($data['status']),
					'invoice_id' => sanitize_text_field($data['invoice_id']),
					'amount'    => sanitize_text_field($data['amount']),
					'currency'  => 'BDT',
					'datetime'  => current_time('mysql', 1)
				],
				['trx_id' => sanitize_text_field($data['trx_id'])]
			);

			if ($update === false) {
				return new WP_Error('update_failed', __('Failed to update transaction', 'sohojpaybd'));
			}

			return $update;
		} else {
			// Insert new transaction
			$insert = $wpdb->insert(
				$table_name,
				[
					'order_id'  => sanitize_text_field($data['order_id']),
					'payment_id' => sanitize_text_field($data['payment_id']),
					'trx_id'    => sanitize_text_field($data['trx_id']),
					'status'    => sanitize_text_field($data['status']),
					'invoice_id' => sanitize_text_field($data['invoice_id']),
					'amount'    => sanitize_text_field($data['amount']),
					'currency'  => 'BDT',
					'datetime'  => current_time('mysql', 1)
				]
			);

			if (is_wp_error($insert)) {
				return $insert;
			}

			return $insert;
		}
	}

	public function update_order_status($order)
	{
		$transactionId = $_REQUEST['transactionId'];
		$data = array(
			"transaction_id" => $transactionId,
		);
		$header = array(
			"api" => get_option('sohojpaybd_api_key'),
			"url" => $this->get_option('payment_site') . "api/payment/verify"
		);

		$response = $this->create_payment($data, $header);
		$data = json_decode($response, true);



		if ($order->get_status() != 'completed') {
			$order_grand_total = (float) $order->get_total();

			$insert_data = [
				'order_id'            => $order->get_id(),
				'payment_id'          => $data['cus_phone'],
				'trx_id'              => $data['transaction_id'],
				'status'              => isset($data['status']) ? $data['status'] : 'N/A',
				'invoice_id'          =>  $data['payment_method'],
				'amount'              => isset($data['amount']) ? floatval($data['amount']) : $order_grand_total,
			];

			$this->sohojpay_insert_transaction($insert_data);

			if ($data['status'] == "COMPLETED") {
				$transaction_id = $data['transaction_id'];
				$amount = $data['amount'];
				$sender_number = $data['cus_email'];
				$payment_method = 'sohojpaybd';

				if ($this->get_option('is_digital') === 'yes') {
					$order->update_status('completed', __("Sohojpaybd payment was successfully completed. Payment Method: {$payment_method}, Amount: {$amount}, Transaction ID: {$transaction_id}, Sender Number: {$sender_number}", 'sohojpaybd'));
					$order->reduce_order_stock();
					$order->add_order_note(__('Payment completed via PGW URL checkout. trx id: ' . $transaction_id, 'sohojpaybd'));
					$order->payment_complete();
				} else {
					$order->update_status('processing', __("Sohojpaybd payment was successfully processed. Payment Method: {$payment_method}, Amount: {$amount}, Transaction ID: {$transaction_id}, Sender Number: {$sender_number}", 'sohojpaybd'));
					$order->reduce_order_stock();
					$order->payment_complete();
				}
				return true;
			} else {
				$order->update_status('on-hold', __('Sohojpaybd payment was successfully on-hold. Transaction id not found. Please check it manually.', 'sohojpaybd'));
				return true;
			}
		}
	}

	/**
	 * Handle Webhook.
	 */
	public function handle_webhook()
	{
		$order_id = $_GET['order_id'];
		$order = wc_get_order($order_id);

		if ($order) {
			$this->update_order_status($order);
		}

		status_header(200);
		echo json_encode(array('success' => true));
		exit;
	}
}

// Add Sohojpaybd Gateway to WooCommerce
function add_sohojpaybd_gateway($methods)
{
	$methods[] = 'WC_Sohojpaybd_Gateway';
	return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_sohojpaybd_gateway');

// Register REST API route
add_action('rest_api_init', function () {
	register_rest_route('sohojpaybd/v1', '/webhook', array(
		'methods'             => 'POST',
		'callback'            => 'sohojpaybd_handle_webhook',
		'permission_callback' => '__return_true',
	));
});

/**
 * Handle the webhook callback.
 */
function sohojpaybd_handle_webhook(WP_REST_Request $request)
{
	$order_id = $request->get_param('order_id');
	$order = wc_get_order($order_id);

	if ($order) {
		$gateway = new WC_Sohojpaybd_Gateway();
		$gateway->update_order_status($order);
	}

	return new WP_REST_Response(array('success' => true), 200);
}
