<?php
/**
 * Plugin Name: CampTix PagSeguro
 * Plugin URI: https://github.com/claudiosmweb/camptix-pagseguro
 * Description: PagSeguro Gateway for CampTix
 * Author: claudiosanches, rafaelfunchal
 * Author URI: http://claudiosmweb.com/
 * Version: 1.3.0
 * License: GPLv2 or later
 * Text Domain: ctpagseguro
 * Domain Path: /languages/
 */

/**
 * CampTix fallback notice.
 */
function ctpagseguro_woocommerce_fallback_notice() {
    $html = '<div class="error">';
        $html .= '<p>' . sprintf( __( 'CampTix PagSeguro Gateway depends on the last version of %s to work!', 'ctpagseguro' ), '<a href="http://wordpress.org/extend/plugins/camptix/">CampTix</a>' ) . '</p>';
    $html .= '</div>';

    echo $html;
}

/**
 * Load functions.
 */
add_action( 'plugins_loaded', 'ctpagseguro_gateway_load', 0 );

function ctpagseguro_gateway_load() {

    if ( ! class_exists( 'CampTix_Plugin' ) || ! class_exists( 'CampTix_Payment_Method' ) ) {
        add_action( 'admin_notices', 'ctpagseguro_woocommerce_fallback_notice' );

        return;
    }

    /**
     * Load textdomain.
     */
    load_plugin_textdomain( 'ctpagseguro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

    /**
     * CampTix_Payment_Method_PagSeguro class.
     */
    class CampTix_Payment_Method_PagSeguro extends CampTix_Payment_Method {

        /**
         * Payment variables.
         */
        public $id = 'pagseguro';
        public $name = 'PagSeguro';
        public $description = 'PagSeguro';
        public $supported_currencies = array( 'BRL' );
        protected $pagseguro_checkout_url = 'https://ws.pagseguro.uol.com.br/v2/checkout/';
        protected $pagseguro_payment_url = 'https://pagseguro.uol.com.br/v2/checkout/payment.html?code=';
        protected $pagseguro_notify_url = 'https://ws.pagseguro.uol.com.br/v2/transactions/notifications/';

        /**
         * Store the options.
         */
        protected $options;

        /**
         * Init the gataway.
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

            // Fix the description for translate.
            $this->description = __( 'PagSeguro Gateway works by sending the user to PagSeguro to enter their payment information.', 'ctpagseguro' );

            add_action( 'template_redirect', array( $this, 'template_redirect' ) );
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
         * Sets the template redirect.
         *
         * @return void
         */
        public function template_redirect() {

            // Payment notify.
            if ( isset( $_POST['notificationCode'] ) && isset( $_POST['notificationType'] ) ) {
                $this->payment_notify();
            }

            // Test the request.
            if ( ! isset( $_REQUEST['tix_payment_method'] ) || 'pagseguro' != $_REQUEST['tix_payment_method'] ) {
                return;
            }

            // Payment return.
            if ( 'payment_return' == get_query_var( 'tix_action' ) ) {
                $this->payment_return();
            }

        }

        /**
         * Get order status.
         *
         * @param  string $payment_token Payment Token
         *
         * @return ixed                  Order status or false.
         */
        protected function get_order_status( $payment_token ) {
            if ( ! $payment_token ) {
                return 0;
            }

            $attendees = get_posts(
                array(
                    'posts_per_page' => -1,
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

            if ( ! $attendees ) {
                return false;
            }

            return $attendees[0]->post_status;
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

            // Sets the post params.
            $params = array(
                'body'      => $body,
                'sslverify' => false,
                'timeout'   => 30
            );

            // Gets the PagSeguro response.
            $response = wp_remote_post( $this->pagseguro_checkout_url, $params );

            // Check to see if the request was valid.
            if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {

                $data = new SimpleXmlElement( $response['body'], LIBXML_NOCDATA );

                $this->log( __( 'PagSeguro payment link created with success!', 'ctpagseguro' ), 0, $response );

                // Payment code.
                return (string) $data->code;
            }

            $this->log( __( 'Failed to generate the PagSeguro payment link', 'ctpagseguro' ), 0, $response );

            return false;
        }

        /**
         * Check notify.
         *
         * @param  string $code PagSeguro transaction code.
         *
         * @return mixed        On success returns an array with the payment data or failure returns false.
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
                'sslverify' => false,
                'timeout'   => 30
            );

            // Gets the PagSeguro response.
            $response = wp_remote_get( $url, $params );

            // Check to see if the request was valid.
            if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
                // Read the PagSeguro return.
                $data = new SimpleXmlElement( $response['body'], LIBXML_NOCDATA );

                $this->log( sprintf( 'Received PagSeguro IPN with success. PagSeguro Payment Code: %s', $code ), 0, $response );

                return $data;
            } else {
                $this->log( sprintf( 'Could not verify PagSeguro IPN. PagSeguro Payment Code: %s', $code ), 0, $response );

                return false;
            }
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
                'reference'        => $payment_token,
                'itemId1'          => '0001',
                'itemDescription1' => trim( substr( $item_description, 0, 95 ) ),
                'itemAmount1'      => $this->money_format( $order['total'] ),
                'itemQuantity1'    => '1'
            );

            // Checks if is localhost. PagSeguro not accept localhost urls!
            if ( ! in_array( $_SERVER['HTTP_HOST'], array( 'localhost', '127.0.0.1' ) ) ) {
                $pagseguro_args['redirectURL'] = add_query_arg( array(
                    'tix_action'         => 'payment_return',
                    'tix_payment_token'  => $payment_token,
                    'tix_payment_method' => 'pagseguro',
                ), $this->get_tickets_url() );

                $pagseguro_args['notificationURL'] = add_query_arg( array(
                    'tix_action'         => 'payment_notify',
                    'tix_payment_token'  => $payment_token,
                    'tix_payment_method' => 'pagseguro',
                ), $this->get_tickets_url() );
            }

            $pagseguro_order = $this->generate_order( $pagseguro_args, $payment_token );

            if ( $pagseguro_order ) {
                wp_redirect( esc_url_raw( $this->pagseguro_payment_url . $pagseguro_order ) );
            } else {
                return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_FAILED );
            }
        }

        /**
         * Convert payment statuses from PagSeguro responses, to CampTix payment statuses.
         *
         * @param  int $payment_status PagSeguro payment status.
         *
         * @return int                 CampTix payment status.
         */
        protected function get_status_from_string( $payment_status ) {
            $statuses = array(
                1 => CampTix_Plugin::PAYMENT_STATUS_PENDING,
                2 => CampTix_Plugin::PAYMENT_STATUS_PENDING,
                3 => CampTix_Plugin::PAYMENT_STATUS_COMPLETED,
                4 => CampTix_Plugin::PAYMENT_STATUS_COMPLETED,
                5 => CampTix_Plugin::PAYMENT_STATUS_REFUNDED,
                6 => CampTix_Plugin::PAYMENT_STATUS_REFUNDED,
                7 => CampTix_Plugin::PAYMENT_STATUS_CANCELLED,
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
         * @return void  Update the order status and/or redirect to order page.
         */
        protected function payment_return() {

            $payment_token = ( isset( $_REQUEST['tix_payment_token'] ) ) ? trim( $_REQUEST['tix_payment_token'] ) : '';

            $current_status = $this->get_order_status( $payment_token );

            if ( 'draft' == $current_status ) {
                return $this->payment_result( $payment_token, CampTix_Plugin::PAYMENT_STATUS_PENDING );
            } else {
                if ( $current_status ) {
                    // Redirects if the status has been changed by IPN.
                    $attendees = get_posts(
                        array(
                            'posts_per_page' => -1,
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

                    $access_token = get_post_meta( $attendees[0]->ID, 'tix_access_token', true );
                    $url = add_query_arg( array( 'tix_action' => 'access_tickets', 'tix_access_token' => $access_token ), $GLOBALS['camptix']->get_tickets_url() );
                    wp_safe_redirect( $url . '#tix' );
                    die();
                }
            }
        }

        /**
         * Process the payment notify.
         *
         * @return void  Update the order status.
         */
        public function payment_notify() {

            $data = $this->check_notify( $_POST['notificationCode'] );

            if ( $data ) {
                $payment_token = $data->reference;
                $status = $this->get_status_from_string( $data->status );

                $payment_data = array(
                    'transaction_id' => (string) $data->code,
                    'transaction_details' => array(
                        'raw' => (array) $data,
                    ),
                );

                return $this->payment_result( $payment_token, $status, $payment_data );
            }
        }

    } // Close CampTix_Payment_Method_PagSeguro class.

    /**
     * Register the Gateway in CampTix.
     */
    camptix_register_addon( 'CampTix_Payment_Method_PagSeguro' );

}
