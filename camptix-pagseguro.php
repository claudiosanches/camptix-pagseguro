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

        /**
         * Store the options.
         */
        protected $options;

        /**
         * Init the gataway options.
         *
         * @return void
         */
        function camptix_init() {
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
        function payment_settings_fields() {
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
        function validate_options( $input ) {
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
         * Process the payment checkout.
         *
         * @param  [type] $payment_token [description]
         *
         * @return [type]                [description]
         */
        function payment_checkout( $payment_token ) {
            global $camptix;

            // Process $order and do something.
            $order = $this->get_order( $payment_token );
            do_action( 'camptix_before_payment', $payment_token );

            $payment_data = array(
                'transaction_id' => 'tix-pagseguro-' . md5( sprintf( 'tix-pagseguro-%s-%s-%s', print_r( $order, true ), time(), rand( 1, 9999 ) ) ),
                'transaction_details' => array(
                    // @todo maybe add more info about the payment
                    'raw' => array( 'payment_method' => 'pagseguro' ),
                ),
            );

            if ( $this->options['always_succeed'] )
                return $this->payment_result( $payment_token, $camptix::PAYMENT_STATUS_COMPLETED, $payment_data );
            else
                return $this->payment_result( $payment_token, $camptix::PAYMENT_STATUS_FAILED );
        }
    }

    /**
     * Register the Gateway in CampTix.
     */
    camptix_register_addon( 'CampTix_Payment_Method_PagSeguro' );

}