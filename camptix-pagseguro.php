<?php
/**
 * Plugin Name: CampTix PagSeguro
 * Plugin URI: https://github.com/claudiosmweb/camptix-pagseguro
 * Description: PagSeguro Gateway for CampTix.
 * Author: claudiosanches
 * Author URI: http://claudiosmweb.com/
 * Version: 1.0.0
 * License: GPLv2 or later
 * Text Domain: ctpagseguro
 * Domain Path: /languages/
 */

/**
 * CampTix fallback notice.
 */
function ctpagseguro_woocommerce_fallback_notice() {
    $html = '<div class="error">';
        $html .= '<p>' . __( 'CampTix PagSeguro Gateway depends on the last version of <a href="http://wordpress.org/extend/plugins/camptix/">CampTix</a> to work!', 'ctpagseguro' ) . '</p>';
    $html .= '</div>';

    echo $html;
}

/**
 * Load functions.
 */
add_action( 'plugins_loaded', 'ctpagseguro_gateway_load', 0 );

function ctpagseguro_gateway_load() {

    if ( ! class_exists( 'CampTix_Payment_Method' ) ) {
        add_action( 'admin_notices', 'ctpagseguro_woocommerce_fallback_notice' );

        return;
    }

    /**
     * Load textdomain.
     */
    load_plugin_textdomain( 'ctpagseguro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    class CampTix_Payment_Method_PagSeguro extends CampTix_Payment_Method {

        /**
         * Payment variables.
         */
        public $id = 'pagseguro';
        public $name = 'PagSeguro';
        public $description = 'PagSeguro Gateway works by sending the user to PagSeguro to enter their payment information.';
        public $supported_currencies = array( 'BRL' );
        protected $pagseguro_checkout_url = 'https://ws.pagseguro.uol.com.br/v2/checkout/';
        protected $pagseguro_payment_url = 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=';

        /**
         * Store the options.
         */
        protected $options;

        /**
         * Init the gataway options.
         *
         * @return void
         */
        public function camptix_init() {
            $this->options = array_merge(
                array(
                    'email' => '',
                    'token' => '',
                ),
                $this->get_payment_options()
            );
        }

        /**
         * Sets payment settings fields.
         *
         * @return void
         */
        public function payment_settings_fields() {
            $this->add_settings_field_helper( 'email', __( 'Email', 'ctpagseguro' ), array( $this, 'field_text' ) );
            $this->add_settings_field_helper( 'token', __( 'Token', 'ctpagseguro' ), array( $this, 'field_text' ) );
        }

        /**
         * Validate options.
         *
         * @param  array $input Options.
         *
         * @return array        Valide options.
         */
        public function validate_options( $input ) {
            $output = $this->options;

            if ( isset( $input['email'] ) ) {
                $output['email'] = $input['email'];
            }

            if ( isset( $input['token'] ) ) {
                $output['token'] = $input['token'];
            }

            return $output;
        }

        /**
         * Get order ID.
         *
         * @param  string $payment_token Payment Token
         *
         * @return int                   Order ID.
         */
        protected function get_order_id( $payment_token ) {
            if ( ! $payment_token ) {
                return 0;
            }

            $attendees = get_posts(
                array(
                    'posts_per_page' => 1,
                    'post_type' => 'tix_attendee',
                    'post_status' => 'any',
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

            if ( ! $attendees ) {
                return 0;
            }

            return (int) $attendees[0]->ID;
        }

        /**
         * PagSeguro money format.
         *
         * @param  int   $number Current number.
         *
         * @return float         Formated number.
         */
        protected function money_format( $number ) {
            return number_format( (float) $number, 2, '.', '' );
        }

        /**
         * Generate the PagSeguro order.
         *
         * @param  array  $args          Payment arguments.
         * @param  string $payment_token Payment token.
         *
         * @return mixed                 Code of payment or false if it fails.
         */
        protected function generate_order( $args, $payment_token ) {
            $body = http_build_query( $args, '', '&' );
            $order_id = $this->get_order_id( $payment_token );

            // Sets the post params.
            $params = array(
                'body'          => $body,
                'sslverify'     => false,
                'timeout'       => 30
            );

            // Gets the PagSeguro response.
            $response = wp_remote_post( $this->pagseguro_checkout_url, $params );

            // Check to see if the request was valid.
            if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {

                $data = new SimpleXmlElement( $response['body'], LIBXML_NOCDATA );

                $this->log( __( 'PagSeguro payment link created with success!', 'ctpagseguro' ), $order_id );

                // Payment code.
                return (string) $data->code;

            }

            $this->log( __( 'Failed to generate the PagSeguro payment link', 'ctpagseguro' ), $order_id );
            return false;
        }

        /**
         * Process the payment checkout.
         *
         * @param  string $payment_token Payment Token.
         *
         * @return mixed                 On success redirects to PagSeguro if fails cancels the purchase.
         */
        public function payment_checkout( $payment_token ) {
            global $camptix;

            if ( ! $payment_token || empty( $payment_token ) ) {
                return false;
            }

            if ( ! in_array( $this->camptix_options['currency'], $this->supported_currencies ) ) {
                die( __( 'The selected currency is not supported by this payment method.', 'ctpagseguro' ) );
            }

            // Process $order and do something.
            $order = $this->get_order( $payment_token );
            do_action( 'camptix_before_payment', $payment_token );

            $payment_data = array(
                'transaction_id' => 'tix-pagseguro-' . md5( sprintf( 'tix-pagseguro-%s-%s-%s', print_r( $order, true ), time(), rand( 1, 9999 ) ) ),
                'transaction_details' => array(
                    'raw' => array( 'payment_method' => 'pagseguro' ),
                ),
            );

            // Sets the PagSeguro item description.
            $item_description = __( 'Event', 'ctpagseguro' );
            if ( isset( $this->camptix_options['event_name'] ) ) {
                $item_description = $this->camptix_options['event_name'];
            }

            foreach ( $order['items'] as $key => $value ) {
                $item_description .= sprintf( ', %sx %s %s', $value['quantity'], $value['name'], $this->money_format( $value['price'] ) );
            }

            $pagseguro_args = array(
                'email'            => $this->options['email'],
                'token'            => $this->options['token'],
                'currency'         => $this->camptix_options['currency'],
                'charset'          => 'UTF-8',
                'itemId1'          => '0001',
                'itemDescription1' => trim( substr( $item_description, 0, 95 ) ),
                'itemAmount1'      => $this->money_format( $order['total'] ),
                'itemQuantity1'    => '1',
                // 'redirectURL'      => '',
                // 'notificationURL'  => '',
            );

            $pagseguro_order = $this->generate_order( $pagseguro_args, $payment_token );
            // echo '<pre>' . print_r( $pagseguro_order, true ) . '</pre>'; exit;

            if ( $pagseguro_order ) {
                wp_redirect( esc_url_raw( $this->pagseguro_payment_url . $pagseguro_order ) );
            } else {
                return $this->payment_result( $payment_token, $camptix::PAYMENT_STATUS_FAILED );
            }
        }
    }

    /**
     * Register the Gateway in CampTix.
     */
    camptix_register_addon( 'CampTix_Payment_Method_PagSeguro' );

}