<?php
/**
 * CampTix PagSeguro Payment Class.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Implements the PagSeguro payment gateway.
 *
 * @package CampTix_PagSeguro/Payment_Method
 */
class CampTix_Payment_Method_PagSeguro extends CampTix_Payment_Method {

	/**
	 * Payment method ID.
	 *
	 * @var string
	 */
	public $id = 'pagseguro';

	/**
	 * Payment method name.
	 *
	 * @var string
	 */
	public $name = 'PagSeguro';

	/**
	 * Payment method description.
	 *
	 * @var string
	 */
	public $description = 'PagSeguro';

	/**
	 * Supported currencies.
	 *
	 * @var array
	 */
	public $supported_currencies = array( 'BRL' );

	/**
	 * PagSeguro checkout URL.
	 *
	 * @var string
	 */
	protected $pagseguro_checkout_url = 'https://ws.pagseguro.uol.com.br/v2/checkout/';

	/**
	 * PagSeguro Payment URL.
	 *
	 * @var string
	 */
	protected $pagseguro_payment_url = 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=';

	/**
	 * PagSeguro notification URL.
	 *
	 * @var string
	 */
	protected $pagseguro_notify_url = 'https://ws.pagseguro.uol.com.br/v2/transactions/notifications/';

	/**
	 * Payment method options.
	 */
	protected $options;

	/**
	 * Initialize the method.
	 */
	public function camptix_init() {
		$this->options = array_merge(
			array(
				'email' => '',
				'token' => '',
			),
			$this->get_payment_options()
		);

		// Make description translatable.
		$this->description = __( 'Receive payments by credit card, bank transfer and backing ticket using PagSeguro.', 'camptix-pagseguro' );

		add_action( 'template_redirect', array( $this, 'template_redirect' ) );
	}

	/**
	 * Sets payment settings fields.
	 */
	public function payment_settings_fields() {
		$this->add_settings_field_helper( 'email', __( 'Email', 'camptix-pagseguro' ), array( $this, 'field_text' ) );
		$this->add_settings_field_helper( 'token', __( 'Token', 'camptix-pagseguro' ), array( $this, 'field_text' ) );
	}

	/**
	 * Validate options.
	 *
	 * @param array $input Options.
	 *
	 * @return array       Valide options.
	 */
	public function validate_options( $input ) {
		$output = $this->options;

		if ( ! empty( $input['email'] ) && is_email( $input['email'] ) ) {
			$output['email'] = $input['email'];
		}

		if ( ! empty( $input['token'] ) ) {
			$output['token'] = $input['token'];
		}

		return $output;
	}

	/**
	 * Sets the template redirect.
	 */
	public function template_redirect() {

		// Test the request.
		if ( empty( $_REQUEST['tix_payment_method'] ) || 'pagseguro' !== $_REQUEST['tix_payment_method'] ) {
			return;
		}

		// Payment return.
		if ( isset( $_GET['tix_action'] ) && 'payment_return' == $_GET['tix_action'] ) {
			return $this->payment_return();
		}

		// Payment notify.
		if ( ! empty( $_POST['notificationCode'] ) && ! empty( $_POST['notificationType'] ) ) {
			return $this->payment_notify();
		}
	}

	/**
	 * PagSeguro currency format.
	 *
	 * @param int $number Current number.
	 *
	 * @return float      Formated number.
	 */
	protected function currency_format( $number ) {
		return number_format( (float) $number, 2, '.', '' );
	}

	/**
	 * Generate the PagSeguro order.
	 *
	 * @param array  $args          Payment arguments.
	 * @param string $payment_token Payment token.
	 *
	 * @return string               Code of payment or empty string if it fails.
	 */
	protected function generate_order( $args, $payment_token ) {
		$body = http_build_query( $args, '', '&' );

		// Sets the post params.
		$params = array(
			'body'    => $body,
			'timeout' => 30,
		);

		// Gets the PagSeguro response.
		$response = wp_remote_post( $this->pagseguro_checkout_url, $params );

		// Check to see if the request was valid.
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {

			$data = new SimpleXmlElement( $response['body'], LIBXML_NOCDATA );

			$this->log( 'PagSeguro payment link created successfully!', 0, $response );

			// Payment code.
			return (string) $data->code;
		}

		$this->log( 'Failed to generate the PagSeguro payment link', 0, $response );

		return false;
	}

	/**
	 * Check notify.
	 *
	 * @param string $code PagSeguro transaction code.
	 *
	 * @return array       On success returns an array with the payment data or failure returns an empty array.
	 */
	protected function check_notify( $code ) {

		// Generate the PagSeguro url.
		$url = sprintf(
			'%s%s?email=%s&token=%s',
			$this->pagseguro_notify_url,
			esc_attr( $code ),
			$this->options['email'],
			$this->options['token']
		);

		// Sets the post params.
		$params = array(
			'timeout' => 30,
		);

		// Gets the PagSeguro response.
		$response = wp_remote_get( esc_url_raw( $url ), $params );

		// Check to see if the request was valid.
		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			// Read the PagSeguro return.
			$data = new SimpleXmlElement( $response['body'], LIBXML_NOCDATA );

			$this->log( sprintf( 'Received PagSeguro IPN successfully. PagSeguro Payment Code: %s', $code ), 0, $response );

			return $data;
		}

		$this->log( sprintf( 'Could not verify PagSeguro IPN. PagSeguro Payment Code: %s', $code ), 0, $response );
		return array();
	}

	/**
	 * Process the payment checkout.
	 *
	 * @param string $payment_token Payment Token.
	 *
	 * @return mixed                On success redirects to PagSeguro if fails cancels the purchase.
	 */
	public function payment_checkout( $payment_token ) {
		global $camptix;

		if ( empty( $payment_token ) ) {
			return false;
		}

		if ( ! in_array( $this->camptix_options['currency'], $this->supported_currencies ) ) {
			die( __( 'The selected currency is not supported by this payment method.', 'camptix-pagseguro' ) );
		}

		// Process $order and do something.
		$order = $this->get_order( $payment_token );
		do_action( 'camptix_before_payment', $payment_token );

		// Sets the PagSeguro item description.
		$item_description = __( 'Event', 'camptix-pagseguro' );
		if ( ! empty( $this->camptix_options['event_name'] ) ) {
			$item_description = $this->camptix_options['event_name'];
		}

		foreach ( $order['items'] as $key => $value ) {
			$item_description .= sprintf( ', %sx %s %s', $value['quantity'], $value['name'], $this->currency_format( $value['price'] ) );
		}

		$pagseguro_args = array(
			'email'            => $this->options['email'],
			'token'            => $this->options['token'],
			'currency'         => $this->camptix_options['currency'],
			'charset'          => 'UTF-8',
			'reference'        => $payment_token,
			'itemId1'          => '0001',
			'itemDescription1' => trim( substr( $item_description, 0, 95 ) ),
			'itemAmount1'      => $this->currency_format( $order['total'] ),
			'itemQuantity1'    => '1',
		);

		// Checks if is localhost. PagSeguro not accept localhost urls!
		if ( ! in_array( $_SERVER['HTTP_HOST'], array( 'localhost', '127.0.0.1' ) ) ) {
			$pagseguro_args['redirectURL'] = add_query_arg( array(
				'tix_action'         => 'payment_return',
				'tix_payment_token'  => $payment_token,
				'tix_payment_method' => 'pagseguro',
			), $this->get_tickets_url() );

			$pagseguro_args['notificationURL'] = add_query_arg( array(
				'tix_payment_method' => 'pagseguro',
			), $this->get_tickets_url() );
		}

		$pagseguro_order = $this->generate_order( $pagseguro_args, $payment_token );

		if ( '' !== $pagseguro_order ) {
			wp_redirect( esc_url_raw( $this->pagseguro_payment_url . $pagseguro_order ) );
			die();
		} else {
			return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_FAILED );
		}
	}

	/**
	 * Convert payment statuses from PagSeguro responses to CampTix payment statuses.
	 *
	 * @param int $payment_status PagSeguro payment status.
	 *
	 * @return int                CampTix payment status.
	 */
	protected function get_payment_status( $payment_status ) {
		$statuses = array(
			1 => CampTix_Plugin::PAYMENT_STATUS_PENDING,
			2 => CampTix_Plugin::PAYMENT_STATUS_PENDING,
			3 => CampTix_Plugin::PAYMENT_STATUS_COMPLETED,
			4 => CampTix_Plugin::PAYMENT_STATUS_COMPLETED,
			5 => CampTix_Plugin::PAYMENT_STATUS_REFUNDED,
			6 => CampTix_Plugin::PAYMENT_STATUS_REFUNDED,
			7 => CampTix_Plugin::PAYMENT_STATUS_CANCELLED
		);

		// Return pending for unknows statuses.
		if ( ! isset( $statuses[ $payment_status ] ) ) {
			$payment_status = 1;
		}

		return $statuses[ $payment_status ];
	}

	/**
	 * Process the payment return.
	 *
	 * @return mixed Update the order status and/or redirect to order page.
	 */
	protected function payment_return() {
		global $camptix;

		$payment_token = ! empty( $_REQUEST['tix_payment_token'] ) ? trim( $_REQUEST['tix_payment_token'] ) : '';
		if ( empty( $payment_token ) ) {
			return;
		}

		$attendees = get_posts(
			array(
				'posts_per_page' => 1,
				'post_type' => 'tix_attendee',
				'post_status' => array( 'draft', 'pending', 'publish', 'cancel', 'refund', 'failed' ),
				'meta_query' => array(
					array(
						'key' => 'tix_payment_token',
						'compare' => '=',
						'value' => $payment_token,
						'type' => 'CHAR',
					),
				),
			)
		);

		if ( empty( $attendees ) ) {
			return;
		}

		$attendee = reset( $attendees );

		if ( 'draft' == $attendee->post_status ) {
			return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_PENDING );
		} else {
			$access_token = get_post_meta( $attendee->ID, 'tix_access_token', true );
			$url = add_query_arg( array(
				'tix_action'       => 'access_tickets',
				'tix_access_token' => $access_token,
			), $camptix->get_tickets_url() );

			wp_safe_redirect( esc_url_raw( $url . '#tix' ) );
			die();
		}
	}

	/**
	 * Process the payment notify.
	 */
	public function payment_notify() {
		if ( empty( $_POST['notificationCode' ] ) ) {
			return;
		}

		$data = $this->check_notify( $_POST['notificationCode'] );

		if ( ! empty( $data ) ) {
			$payment_token = (string) $data->reference;
			$status = $this->get_payment_status( (int) $data->status );

			$payment_data = array(
				'transaction_id' => (string) $data->code,
				'transaction_details' => array(
					'raw' => array(
						'data' => (string) $data->date
					),
				),
			);

			return $this->payment_result( $payment_token, $status, $payment_data );
		}
	}
}
